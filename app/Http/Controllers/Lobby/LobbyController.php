<?php

namespace App\Http\Controllers\Lobby;

use App\Events\LobbyUpdated;
use App\Http\Controllers\Controller;
use App\Models\Lobby;
use App\Models\LobbyMatch;
use App\Models\Server;
use App\Services\RconClient;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class LobbyController extends Controller
{
    public function show(Server $server): View
    {
        if (! $server->isOnline()) {
            return redirect()->route('home')->with('server_error', 'Servidor offline. Intenta mas tarde.');
        }

        [$server, $lobby, $isReady, $missingPlayers] = $this->resolveLobbyState($server, Auth::id());

        [$teamSize, $ctCount, $tCount] = $this->teamSnapshot($lobby, $server);
        $currentTeam = $this->currentUserTeam($lobby, Auth::id());

        return view('lobby', [
            'server' => $server,
            'lobby' => $lobby,
            'isReady' => $isReady,
            'missingPlayers' => $missingPlayers,
            'teamSize' => $teamSize,
            'ctCount' => $ctCount,
            'tCount' => $tCount,
            'currentTeam' => $currentTeam,
        ]);
    }

    public function status(Server $server): JsonResponse
    {
        if (! $server->isOnline()) {
            return response()->json(['offline' => true], 409);
        }

        [$server, $lobby, $isReady, $missingPlayers] = $this->resolveLobbyState($server, Auth::id());

        return response()->json($this->buildPayload($server, $lobby, $isReady, $missingPlayers));
    }

    public function leave(Server $server): JsonResponse
    {
        $userId = Auth::id();

        $lobby = $server->lobbies()
            ->whereIn('status', ['waiting', 'live'])
            ->whereHas('users', fn ($query) => $query->where('users.id', $userId))
            ->withCount('users')
            ->latest('id')
            ->first();

        if (! $lobby) {
            return response()->json(['left' => true]);
        }

        if ($this->isLocked($lobby)) {
            return response()->json(['message' => 'Match iniciado. No puedes abandonar.'], 409);
        }

        $lobby->users()->detach($userId);
        $lobby->loadCount('users');

        if ($lobby->users_count < $lobby->required_players) {
            $lobby->update([
                'status' => 'waiting',
                'started_at' => null,
            ]);
        }

        $this->syncServerPlayers($server);

        $lobby = $lobby->fresh()->load('users:id,name,steam_nickname,avatar,rank_points')->loadCount('users');
        $server = $server->fresh();

        $threshold = $this->revealThreshold($server);
        $isReady = $lobby->users_count >= $threshold;
        $missingPlayers = max(0, $threshold - $lobby->users_count);

        $this->broadcastLobby($server, $lobby, $isReady, $missingPlayers);

        return response()->json(['left' => true]);
    }

    public function setTeam(Server $server): JsonResponse
    {
        $userId = Auth::id();
        $team = request()->string('team')->lower()->value();

        if (! in_array($team, ['ct', 't'], true)) {
            return response()->json(['message' => 'Equipo invalido'], 422);
        }

        $lobby = $server->lobbies()
            ->whereIn('status', ['waiting', 'live'])
            ->whereHas('users', fn ($query) => $query->where('users.id', $userId))
            ->latest('id')
            ->first();

        if (! $lobby) {
            return response()->json(['message' => 'Lobby no encontrado'], 404);
        }

        if ($this->isLocked($lobby)) {
            return response()->json(['message' => 'Match iniciado. No puedes cambiar equipo.'], 409);
        }

        $teamSize = $this->teamSize($lobby, $server);
        [$ctCount, $tCount] = $this->teamCounts($lobby);

        if ($team === 'ct' && $ctCount >= $teamSize) {
            return response()->json(['message' => 'Equipo lleno'], 409);
        }
        if ($team === 't' && $tCount >= $teamSize) {
            return response()->json(['message' => 'Equipo lleno'], 409);
        }

        $lobby->users()->updateExistingPivot($userId, ['team' => $team]);

        $lobby = $lobby->fresh()->load('users:id,name,steam_nickname,avatar,rank_points')->loadCount('users');
        $server = $server->fresh();
        $threshold = $this->revealThreshold($server);
        $isReady = $lobby->users_count >= $threshold;
        $missingPlayers = max(0, $threshold - $lobby->users_count);

        $this->broadcastLobby($server, $lobby, $isReady, $missingPlayers);

        return response()->json($this->buildPayload($server, $lobby, $isReady, $missingPlayers));
    }

    /**
     * @return array{Server, Lobby, bool, int}
     */
    private function resolveLobbyState(Server $server, int $userId): array
    {
        $displayRequiredPlayers = min($server->max_players, 10);
        $revealThreshold = $this->revealThreshold($server);
        $shouldBroadcast = false;

        $lobby = $server->lobbies()
            ->whereIn('status', ['waiting', 'live'])
            ->withCount('users')
            ->whereRaw('(select count(*) from lobby_user where lobby_user.lobby_id = lobbies.id) < lobbies.required_players')
            ->orderByRaw("case when status = 'waiting' then 0 else 1 end")
            ->latest('id')
            ->first();

        if (! $lobby) {
            $lobby = $server->lobbies()->create([
                'status' => 'waiting',
                'name' => sprintf('Lobby %s #%s', $server->name, now()->format('His')),
                'required_players' => $displayRequiredPlayers,
            ]);
            $shouldBroadcast = true;
        }

        if ($lobby->required_players !== $displayRequiredPlayers) {
            $lobby->update(['required_players' => $displayRequiredPlayers]);
            $shouldBroadcast = true;
        }

        $alreadyInLobby = $lobby->users()->where('users.id', $userId)->exists();
        $currentCount = $lobby->users()->count();

        if (! $alreadyInLobby && $currentCount < $lobby->required_players && ! $this->isLocked($lobby)) {
            $lobby->users()->syncWithoutDetaching([$userId]);
            $shouldBroadcast = true;
        }

        $teamSize = $this->teamSize($lobby, $server);
        if ($this->ensureTeam($lobby, $userId, $teamSize)) {
            $shouldBroadcast = true;
        }

        $lobby->loadCount('users');
        $playerCount = $lobby->users_count;

        if ($playerCount >= $revealThreshold && $lobby->status === 'waiting') {
            $lobby->update([
                'status' => 'live',
                'started_at' => now(),
            ]);
            $shouldBroadcast = true;
        }

        $this->startMatchIfReady($server, $lobby);

        $this->syncServerPlayers($server);

        $lobby = $lobby->fresh()->load('users:id,name,steam_nickname,avatar,rank_points')->loadCount('users');
        $server = $server->fresh();

        $isReady = $lobby->users_count >= $revealThreshold;
        $missingPlayers = max(0, $revealThreshold - $lobby->users_count);

        if ($shouldBroadcast) {
            $this->broadcastLobby($server, $lobby, $isReady, $missingPlayers);
        }

        return [$server, $lobby, $isReady, $missingPlayers];
    }

    private function broadcastLobby(Server $server, Lobby $lobby, bool $isReady, int $missingPlayers): void
    {
        broadcast(new LobbyUpdated(
            $server->id,
            $this->buildPayload($server, $lobby, $isReady, $missingPlayers)
        ));
    }

    private function buildPayload(Server $server, Lobby $lobby, bool $isReady, int $missingPlayers): array
    {
        [$teamSize, $ctCount, $tCount] = $this->teamSnapshot($lobby, $server);
        $currentTeam = $this->currentUserTeam($lobby, Auth::id());

        return [
            'server' => [
                'id' => $server->id,
                'name' => $server->name,
                'ip' => $server->ip,
                'port' => $server->port,
            ],
            'lobby' => [
                'id' => $lobby->id,
                'status' => $lobby->status,
                'required_players' => $lobby->required_players,
                'users_count' => $lobby->users_count,
                'missing_players' => $missingPlayers,
                'team_size' => $teamSize,
                'ct_count' => $ctCount,
                't_count' => $tCount,
                'current_team' => $currentTeam,
                'locked' => $this->isLocked($lobby),
            ],
            'is_ready' => $isReady,
            'users' => $lobby->users->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->steam_nickname ?? $user->name,
                'avatar' => $user->avatar,
                'rank_points' => $user->rank_points,
                'team' => $user->pivot?->team,
            ])->values(),
        ];
    }

    private function syncServerPlayers(Server $server): void
    {
        $playersInActiveLobby = Lobby::query()
            ->where('server_id', $server->id)
            ->whereIn('status', ['waiting', 'live'])
            ->withCount('users')
            ->get()
            ->max('users_count') ?? 0;

        if ($server->current_players !== $playersInActiveLobby) {
            $server->update([
                'current_players' => $playersInActiveLobby,
            ]);
        }
    }

    private function teamSize(Lobby $lobby, Server $server): int
    {
        $required = $lobby->required_players ?: min($server->max_players, 10);
        return max(1, intdiv($required, 2));
    }

    /**
     * @return array{int, int}
     */
    private function teamCounts(Lobby $lobby): array
    {
        $ctCount = $lobby->users()->wherePivot('team', 'ct')->count();
        $tCount = $lobby->users()->wherePivot('team', 't')->count();

        return [$ctCount, $tCount];
    }

    /**
     * @return array{int, int, int}
     */
    private function teamSnapshot(Lobby $lobby, Server $server): array
    {
        $teamSize = $this->teamSize($lobby, $server);
        [$ctCount, $tCount] = $this->teamCounts($lobby);

        return [$teamSize, $ctCount, $tCount];
    }

    private function currentUserTeam(Lobby $lobby, int $userId): ?string
    {
        $user = $lobby->users()->where('users.id', $userId)->first();

        return $user?->pivot?->team;
    }

    private function ensureTeam(Lobby $lobby, int $userId, int $teamSize): bool
    {
        if ($this->isLocked($lobby)) {
            return false;
        }

        $user = $lobby->users()->where('users.id', $userId)->first();

        if (! $user) {
            return false;
        }

        $currentTeam = $user->pivot?->team;
        if ($currentTeam) {
            return false;
        }

        [$ctCount, $tCount] = $this->teamCounts($lobby);

        $preferred = $ctCount <= $tCount ? 'ct' : 't';
        $alternate = $preferred === 'ct' ? 't' : 'ct';

        if ($preferred === 'ct' && $ctCount < $teamSize) {
            $lobby->users()->updateExistingPivot($userId, ['team' => 'ct']);
            return true;
        }

        if ($preferred === 't' && $tCount < $teamSize) {
            $lobby->users()->updateExistingPivot($userId, ['team' => 't']);
            return true;
        }

        if ($alternate === 'ct' && $ctCount < $teamSize) {
            $lobby->users()->updateExistingPivot($userId, ['team' => 'ct']);
            return true;
        }

        if ($alternate === 't' && $tCount < $teamSize) {
            $lobby->users()->updateExistingPivot($userId, ['team' => 't']);
            return true;
        }

        return false;
    }

    private function isLocked(Lobby $lobby): bool
    {
        return $lobby->status === 'live' && $lobby->started_at !== null;
    }

    private function revealThreshold(Server $server): int
    {
        return 2;
    }

    private function startMatchIfReady(Server $server, Lobby $lobby): void
    {
        if ($server->type !== 'mm') {
            return;
        }

        $threshold = $this->revealThreshold($server);
        if ($lobby->users()->count() < $threshold) {
            return;
        }

        if (LobbyMatch::query()->where('lobby_id', $lobby->id)->exists()) {
            return;
        }

        $token = trim((string) env('GET5_WEBHOOK_TOKEN'));
        if ($token === '') {
            return;
        }

        $baseUrl = rtrim((string) config('app.url'), '/');
        if ($baseUrl === '') {
            return;
        }

        $rconHost = (string) env('RCON_HOST', $server->ip);
        $rconPort = (int) env('RCON_PORT', $server->port);
        $rconPassword = (string) env('RCON_PASSWORD', '');

        if ($rconPassword === '') {
            return;
        }

        $url = $baseUrl . '/api/get5/match/' . $lobby->id;
        $command = sprintf(
            'get5_loadmatch_url "%s" "Authorization" "Bearer %s"',
            $url,
            $token
        );

        app(RconClient::class)->send($rconHost, $rconPort, $rconPassword, $command);
    }
}