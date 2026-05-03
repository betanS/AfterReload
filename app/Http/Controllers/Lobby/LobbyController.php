<?php

namespace App\Http\Controllers\Lobby;

use App\Events\LobbyUpdated;
use App\Http\Controllers\Api\Get5Controller;
use App\Http\Controllers\Controller;
use App\Models\Lobby;
use App\Models\LobbyMatch;
use App\Models\Server;
use App\Services\RconClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LobbyController extends Controller
{
    public function show(Server $server): View
    {
        $this->ensureBetaAccess($server);

        if (!$server->isOnline() && !app()->environment('testing')) {
            return redirect()->route('home')->with('server_error', 'Servidor offline. Intenta mas tarde.');
        }

        $userId = Auth::id();
        
        // Remove user from any other lobbies they might be in
        \DB::table('lobby_user')
            ->where('user_id', $userId)
            ->whereNotExists(function ($query) use ($server) {
                $query->select(\DB::raw(1))
                    ->from('lobbies')
                    ->whereColumn('lobbies.id', 'lobby_user.lobby_id')
                    ->where('lobbies.server_id', $server->id);
            })
            ->delete();

        [$server, $lobby, $isReady, $missingPlayers] = $this->resolveLobbyState($server);
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
            'teamSize' => (int) ($lobby->required_players / 2),
            'ctCount' => $lobby->users->where('pivot.team', 'ct')->count(),
            'tCount' => $lobby->users->where('pivot.team', 't')->count(),
        ]);
    }

    public function status(Server $server): JsonResponse
    {
        $this->ensureBetaAccess($server);

        if (!$server->isOnline() && !app()->environment('testing')) {
            return response()->json(['offline' => true], 409);
        }

        [$server, $lobby, $isReady, $missingPlayers] = $this->resolveLobbyState($server);

        return response()->json($this->buildPayload($server, $lobby, $isReady, $missingPlayers));
    }

    public function leave(Server $server): JsonResponse
    {
        $userId = Auth::id();
        $lobby = $this->getActiveLobby($server, $userId);

        if (!$lobby) {
            return response()->json(['left' => true]);
        }

        if ($this->isMatchInProgress($lobby, $server)) {
            return response()->json(['message' => 'Match iniciado. No puedes abandonar.'], 409);
        }

        $lobby->users()->detach($userId);
        $this->refreshLobbyStatus($lobby);
        $this->syncServerPlayers($server);
        $this->broadcastUpdate($server, $lobby);

        return response()->json(['left' => true]);
    }

    public function setTeam(Server $server): JsonResponse
    {
        $userId = Auth::id();
        $team = request()->string('team')->lower()->value();

        if ($server->type !== 'public' && !in_array($team, ['ct', 't'], true)) {
            return response()->json(['message' => 'Equipo invalido'], 422);
        }

        // For public servers, we just use 'ct' as the default team internally
        if ($server->type === 'public') {
            $team = 'ct';
        }

        $lobby = $this->getOrCreateWaitingLobby($server);

        if ($this->isMatchInProgress($lobby, $server)) {
            return response()->json(['message' => 'No puedes unirte ahora.'], 409);
        }

        $maxPlayers = $server->max_players;
        $currentCount = $lobby->users()->count();
        $isAlreadyIn = $lobby->users()->where('users.id', $userId)->exists();

        if (!$isAlreadyIn && $currentCount >= $maxPlayers) {
            return response()->json(['message' => 'Servidor lleno'], 409);
        }

        $lobby->users()->syncWithoutDetaching([
            $userId => ['team' => $team, 'is_ready' => false],
        ]);

        $this->syncServerPlayers($server);
        $lobby = $lobby->fresh();
        $this->broadcastUpdate($server, $lobby);

        return response()->json($this->payloadFor($server, $lobby));
    }

    public function toggleReady(Server $server): JsonResponse
    {
        $userId = Auth::id();
        $lobby = $this->getActiveLobby($server, $userId);

        if (!$lobby || $this->isMatchInProgress($lobby, $server)) {
            return response()->json(['message' => 'No puedes cambiar estado ahora.'], 409);
        }

        $user = $lobby->users()->where('users.id', $userId)->first();
        if (!$user?->pivot?->team) {
            return response()->json(['message' => 'Primero elige CT o T.'], 409);
        }

        $lobby->users()->updateExistingPivot($userId, [
            'is_ready' => !($user->pivot->is_ready ?? false),
        ]);

        $this->checkAndStartMatch($server, $lobby);
        $this->syncServerPlayers($server);

        $lobby = $lobby->fresh();
        $this->broadcastUpdate($server, $lobby);

        return response()->json($this->payloadFor($server, $lobby));
    }

    private function getActiveLobby(Server $server, int $userId): ?Lobby
    {
        return $server->lobbies()
            ->whereIn('status', ['waiting', 'live'])
            ->whereHas('users', fn ($query) => $query->where('users.id', $userId))
            ->latest('id')
            ->first();
    }

    private function resolveLobbyState(Server $server): array
    {
        $lobby = $this->getOrCreateWaitingLobby($server);

        $this->checkAndStartMatch($server, $lobby);
        $this->syncServerPlayers($server);

        $required = min($server->max_players, 10);
        $isReady = $server->type !== 'public' && $this->currentMatch($lobby) !== null;
        $missing = max(0, $required - $lobby->users()->count());

        return [$server, $lobby, $isReady, $missing];
    }

    private function getOrCreateWaitingLobby(Server $server): Lobby
    {
        $lobby = $server->lobbies()
            ->whereIn('status', ['waiting', 'live'])
            ->latest('id')
            ->first();

        if ($lobby) {
            return $lobby;
        }

        return $server->lobbies()->create([
            'status' => 'waiting',
            'name' => 'Lobby ' . $server->name,
            'required_players' => min($server->max_players, 10),
        ]);
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
            ->map(fn (Lobby $lobby) => $lobby->users()->count())
            ->max() ?? 0;

        $server->update(['current_players' => $count]);
    }

    private function broadcastUpdate(Server $server, Lobby $lobby): void
    {
        broadcast(new LobbyUpdated($server->id, $this->payloadFor($server, $lobby)));
    }

    private function payloadFor(Server $server, Lobby $lobby): array
    {
        $required = min($server->max_players, 10);
        $missing = max(0, $required - $lobby->users()->count());
        $isReady = $server->type !== 'public' && $this->currentMatch($lobby) !== null;

        return $this->buildPayload($server, $lobby, $isReady, $missing);
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
                'id' => $lobby->id,
                'status' => $lobby->status,
                'is_unlimited' => $isUnlimited,
                'required_players' => $lobby->required_players,
                'users_count' => $lobby->users->count(),
                'missing_players' => $missing,
                'team_size' => (int) ($lobby->required_players / 2),
                'ct_count' => $lobby->users->where('pivot.team', 'ct')->count(),
                't_count' => $lobby->users->where('pivot.team', 't')->count(),
                'locked' => $this->isMatchInProgress($lobby, $server),
            ],
            'is_ready' => $isReady,
            'users' => $lobby->users->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->steam_nickname ?? $user->name,
                'avatar' => $user->avatar,
                'rank_points' => $user->rank_points,
                'team' => $user->pivot->team,
                'is_ready' => (bool) $user->pivot->is_ready,
                'is_current_user' => $user->id === Auth::id(),
            ]),
        ];
    }

    private function checkAndStartMatch(Server $server, Lobby $lobby): void
    {
        if ($server->type === 'public' || $this->currentMatch($lobby) !== null) {
            return;
        }

        $lobby->load('users');
        $players = $lobby->users->filter(fn ($user) => !empty($user->steam_id));
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

        app(Get5Controller::class)->buildOrCreateLobbyMatch($lobby);
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
        if ($server->type !== 'mm') {
            return;
        }

        $user = Auth::user();
        if ($user && ($user->isAdmin() || $user->isBetaTester())) {
            return;
        }

        abort(403, 'Acceso restringido.');
    }
}
