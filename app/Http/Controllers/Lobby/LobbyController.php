<?php

namespace App\Http\Controllers\Lobby;

use App\Events\LobbyUpdated;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Get5Controller;
use App\Models\Lobby;
use App\Models\LobbyMatch;
use App\Models\Server;
use App\Services\RconClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class LobbyController extends Controller
{
    /**
     * Muestra la vista del lobby para un servidor específico.
     */
    public function show(Server $server): View
    {
        $this->ensureBetaAccess($server);

        if (!$server->isOnline() && !app()->environment('testing')) {
            return redirect()->route('home')->with('server_error', 'Servidor offline. Intenta mas tarde.');
        }

        // Cargamos el estado actual del lobby
        [$server, $lobby, $isReady, $missingPlayers] = $this->resolveLobbyState($server, Auth::id());
        $this->updateHeartbeat($server, Auth::id());
        $lobby->load('users:id,name,steam_nickname,avatar,rank_points');
        $lobby->loadCount('users');

        return view('lobby', [
            'server' => $server,
            'lobby' => $lobby,
            'isReady' => $isReady,
            'missingPlayers' => $missingPlayers,
            'currentTeam' => $this->getUserTeam($lobby, Auth::id()),
            'isUnlimitedLobby' => $server->type === 'public',
            'displayLobbyId' => $lobby->id,
            'teamSize' => (int)($lobby->required_players / 2),
            'ctCount' => $lobby->users->where('pivot.team', 'ct')->count(),
            'tCount' => $lobby->users->where('pivot.team', 't')->count(),
        ]);
    }

    /**
     * Devuelve el estado del lobby en JSON (utilizado para polling/actualización en tiempo real).
     */
    public function status(Server $server): JsonResponse
    {
        $this->ensureBetaAccess($server);

        if (!$server->isOnline() && !app()->environment('testing')) {
            return response()->json(['offline' => true], 409);
        }

        $this->updateHeartbeat($server, Auth::id());
        [$server, $lobby, $isReady, $missingPlayers] = $this->resolveLobbyState($server, Auth::id());
        
        return response()->json($this->buildPayload($server, $lobby, $isReady, $missingPlayers));
    }

    /**
     * Actualiza el "latido" del usuario para confirmar que sigue conectado al lobby.
     */
    public function heartbeat(Server $server): JsonResponse
    {
        $this->updateHeartbeat($server, Auth::id());
        return response()->json(['ok' => true]);
    }

    /**
     * Permite a un usuario abandonar el lobby actual.
     */
    public function leave(Server $server): JsonResponse
    {
        $userId = Auth::id();
        $lobby = $this->getActiveLobby($server, $userId);

        if (!$lobby) return response()->json(['left' => true]);

        if ($this->isMatchInProgress($lobby, $server)) {
            return response()->json(['message' => 'Match iniciado. No puedes abandonar.'], 409);
        }

        $lobby->users()->detach($userId);
        Cache::forget("lobby:{$lobby->id}:user:{$userId}:heartbeat");
        $this->refreshLobbyStatus($lobby);
        $this->syncServerPlayers($server);
        $this->broadcastUpdate($server, $lobby);

        return response()->json(['left' => true]);
    }

    /**
     * Permite elegir equipo (CT o T).
     */
    public function setTeam(Server $server): JsonResponse
    {
        $userId = Auth::id();
        $team = request()->string('team')->lower()->value();

        if (!in_array($team, ['ct', 't'])) {
            return response()->json(['message' => 'Equipo inválido'], 422);
        }

        $lobby = $this->getActiveLobby($server, $userId);
        if (!$lobby || $this->isMatchInProgress($lobby, $server)) {
            return response()->json(['message' => 'No puedes cambiar de equipo ahora.'], 409);
        }

        $teamSize = (int)($lobby->required_players / 2);
        $count = $lobby->users()->wherePivot('team', $team)->count();

        if ($count >= $teamSize) {
            return response()->json(['message' => 'Equipo lleno'], 409);
        }

        $lobby->users()->updateExistingPivot($userId, ['team' => $team]);
        $this->broadcastUpdate($server, $lobby);

        return response()->json(['ok' => true]);
    }

    /**
     * Cambia el estado de "Listo" del usuario.
     */
    public function toggleReady(Server $server): JsonResponse
    {
        $userId = Auth::id();
        $lobby = $this->getActiveLobby($server, $userId);

        if (!$lobby || $this->isMatchInProgress($lobby, $server)) {
            return response()->json(['message' => 'No puedes cambiar estado ahora.'], 409);
        }

        $user = $lobby->users()->where('users.id', $userId)->first();
        $newState = !($user->pivot->is_ready ?? false);
        
        $lobby->users()->updateExistingPivot($userId, ['is_ready' => $newState]);
        $this->checkAndStartMatch($server, $lobby);
        $this->syncServerPlayers($server);

        $lobby = $lobby->fresh();
        $this->broadcastUpdate($server, $lobby);

        $required = min($server->max_players, 10);
        $missing = max(0, $required - $lobby->users()->count());
        $isReady = $this->currentMatch($lobby) !== null;

        return response()->json($this->buildPayload($server, $lobby, $isReady, $missing));
    }

    // --- MÉTODOS PRIVADOS DE LÓGICA ---

    private function updateHeartbeat(Server $server, int $userId): void
    {
        $lobby = $this->getActiveLobby($server, $userId);
        if ($lobby) {
            Cache::put("lobby:{$lobby->id}:user:{$userId}:heartbeat", now()->timestamp, 60);
        }
    }

    private function getActiveLobby(Server $server, int $userId): ?Lobby
    {
        return $server->lobbies()
            ->whereIn('status', ['waiting', 'live'])
            ->whereHas('users', fn($q) => $q->where('users.id', $userId))
            ->latest('id')->first();
    }

    private function resolveLobbyState(Server $server, int $userId): array
    {
        // Limpiar inactivos (antes en LobbyHeartbeatCleaner)
        $this->cleanupInactives($server, $userId);

        $required = min($server->max_players, 10);
        $lobby = $server->lobbies()->whereIn('status', ['waiting', 'live'])->latest('id')->first();

        if (!$lobby || $lobby->status === 'completed') {
            $lobby = $server->lobbies()->create([
                'status' => 'waiting',
                'name' => 'Lobby ' . $server->name,
                'required_players' => $required,
            ]);
        }

        // Auto-unirse si hay hueco
        if (!$lobby->users()->where('users.id', $userId)->exists() && !$this->isMatchInProgress($lobby, $server)) {
            if ($server->type === 'public' || $lobby->users()->count() < $lobby->required_players) {
                $lobby->users()->syncWithoutDetaching([$userId]);
                // Asignar equipo automático
                $ct = $lobby->users()->wherePivot('team', 'ct')->count();
                $t = $lobby->users()->wherePivot('team', 't')->count();
                $lobby->users()->updateExistingPivot($userId, ['team' => ($ct <= $t ? 'ct' : 't')]);
            }
        }

        $this->checkAndStartMatch($server, $lobby);
        $this->syncServerPlayers($server);

        $isReady = ($server->type !== 'public' && $this->currentMatch($lobby) !== null);
        $missing = max(0, $required - $lobby->users()->count());

        return [$server, $lobby, $isReady, $missing];
    }

    private function cleanupInactives(Server $server, int $skipUserId): void
    {
        $lobbies = $server->lobbies()->whereIn('status', ['waiting', 'live'])->get();
        foreach ($lobbies as $lobby) {
            if ($this->isMatchInProgress($lobby, $server)) continue;

            $inactiveIds = [];
            foreach ($lobby->users as $user) {
                if ($user->id === $skipUserId) continue;
                $lastSeen = Cache::get("lobby:{$lobby->id}:user:{$user->id}:heartbeat");
                if (!$lastSeen || (now()->timestamp - $lastSeen) > 35) {
                    $inactiveIds[] = $user->id;
                }
            }

            if (!empty($inactiveIds)) {
                $lobby->users()->detach($inactiveIds);
                foreach ($inactiveIds as $inactiveId) {
                    Cache::forget("lobby:{$lobby->id}:user:{$inactiveId}:heartbeat");
                }
                $this->refreshLobbyStatus($lobby);
            }
        }
    }

    private function refreshLobbyStatus(Lobby $lobby): void
    {
        if ($lobby->users()->count() < $lobby->required_players) {
            $lobby->update(['status' => 'waiting', 'started_at' => null]);
        }
    }

    private function isMatchInProgress(Lobby $lobby, Server $server): bool
    {
        return $server->type !== 'public' && $this->currentMatch($lobby) !== null && $lobby->started_at !== null;
    }

    private function syncServerPlayers(Server $server): void
    {
        $count = $server->lobbies()
            ->whereIn('status', ['waiting', 'live'])
            ->get()
            ->map(fn (Lobby $lobby) => $this->activeUsersCount($lobby))
            ->max() ?? 0;

        $server->update(['current_players' => $count]);
    }

    private function activeUsersCount(Lobby $lobby): int
    {
        $lobby->loadMissing('users:id');

        return $lobby->users
            ->filter(function ($user) use ($lobby) {
                $lastSeen = Cache::get("lobby:{$lobby->id}:user:{$user->id}:heartbeat");

                return $lastSeen && (now()->timestamp - (int) $lastSeen) <= 35;
            })
            ->count();
    }

    private function broadcastUpdate(Server $server, Lobby $lobby): void
    {
        $required = min($server->max_players, 10);
        $missing = max(0, $required - $lobby->users()->count());
        $isReady = $server->type !== 'public' && $this->currentMatch($lobby) !== null;
        
        broadcast(new LobbyUpdated($server->id, $this->buildPayload($server, $lobby, $isReady, $missing)));
    }

    private function buildPayload(Server $server, Lobby $lobby, bool $isReady, int $missing): array
    {
        $lobby->load('users');
        $isUnlimited = $server->type === 'public';
        $match = $this->currentMatch($lobby);
        $password = data_get($match?->config, 'password');

        return [
            'server' => array_merge(
                $server->only(['id', 'name', 'type', 'ip', 'port']),
                ['password' => is_string($password) && $password !== '' ? $password : null]
            ),
            'lobby' => [
                'id' => $server->id,
                'status' => $lobby->status,
                'is_unlimited' => $isUnlimited,
                'required_players' => $lobby->required_players,
                'users_count' => $lobby->users->count(),
                'missing_players' => $missing,
                'team_size' => (int)($lobby->required_players / 2),
                'ct_count' => $lobby->users->where('pivot.team', 'ct')->count(),
                't_count' => $lobby->users->where('pivot.team', 't')->count(),
                'locked' => $this->isMatchInProgress($lobby, $server),
            ],
            'is_ready' => $isReady,
            'users' => $lobby->users->map(fn($u) => [
                'id' => $u->id,
                'name' => $u->steam_nickname ?? $u->name,
                'avatar' => $u->avatar,
                'rank_points' => $u->rank_points,
                'team' => $u->pivot->team,
                'is_ready' => (bool)$u->pivot->is_ready,
                'is_current_user' => $u->id === Auth::id(),
            ]),
        ];
    }

    private function checkAndStartMatch(Server $server, Lobby $lobby): void
    {
        if ($server->type === 'public' || $this->currentMatch($lobby) !== null) {
            return;
        }

        $lobby->load('users');
        $players = $lobby->users->filter(fn ($user) => ! empty($user->steam_id));
        $playerCount = $players->count();

        if ($playerCount < 2) {
            return;
        }

        $readyCount = $players->filter(fn ($user) => (bool) $user->pivot?->is_ready)->count();
        $threshold = (int) ceil($playerCount / 2);
        $ctCount = $players->where('pivot.team', 'ct')->count();
        $tCount = $players->where('pivot.team', 't')->count();

        if ($readyCount < $threshold || $ctCount === 0 || $ctCount !== $tCount) {
            return;
        }

        $config = app(Get5Controller::class)->buildOrCreateLobbyMatch($lobby);
        $matchUrl = route('api.get5.match', $lobby);
        $token = trim((string) env('GET5_WEBHOOK_TOKEN'));

        $rcon = app(RconClient::class);
        $rconHost = (string) env('RCON_HOST', $server->ip);
        $rconPort = (int) env('RCON_PORT', $server->port);
        $rconPassword = (string) env('RCON_PASSWORD', $server->rcon_password ?: '');

        if ($rconPassword !== '') {
            $rcon->send($rconHost, $rconPort, $rconPassword, 'get5_server_id "' . $server->id . '"');
            $rcon->send(
                $rconHost,
                $rconPort,
                $rconPassword,
                'get5_loadmatch_url "' . $matchUrl . '" "Authorization" "Bearer ' . $token . '"'
            );
        }

        $lobby->update([
            'status' => 'live',
            'started_at' => now(),
            'required_players' => $playerCount,
        ]);
    }

    private function currentMatch(Lobby $lobby): ?LobbyMatch
    {
        return LobbyMatch::query()
            ->where('lobby_id', $lobby->id)
            ->whereIn('status', ['pending', 'live'])
            ->latest('id')
            ->first();
    }

    private function getUserTeam(Lobby $lobby, int $userId): ?string
    {
        return $lobby->users()->where('users.id', $userId)->first()?->pivot?->team;
    }

    private function ensureBetaAccess(Server $server): void
    {
        if ($server->type !== 'mm') return;
        $user = Auth::user();
        if ($user && ($user->isAdmin() || $user->isBetaTester())) return;
        abort(403, 'Acceso restringido.');
    }
}
