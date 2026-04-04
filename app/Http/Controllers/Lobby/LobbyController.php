<?php

namespace App\Http\Controllers\Lobby;

use App\Events\LobbyUpdated;
use App\Http\Controllers\Controller;
use App\Models\Lobby;
use App\Models\LobbyMatch;
use App\Models\Server;
use App\Services\LobbyHeartbeatCleaner;
use App\Services\RconClient;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class LobbyController extends Controller
{
    public function show(Server $server): View
    {
        $this->ensureBetaAccess($server);

        if (! $server->isOnline()) {
            return redirect()->route('home')->with('server_error', 'Servidor offline. Intenta mas tarde.');
        }

        [$server, $lobby, $isReady, $missingPlayers] = $this->resolveLobbyState($server, Auth::id());

        // Ensure users are loaded for the view
        $lobby->load('users:id,name,steam_nickname,avatar,rank_points');

        [$teamSize, $ctCount, $tCount] = $this->teamSnapshot($lobby, $server);
        $currentTeam = $this->currentUserTeam($lobby, Auth::id());

        return view('lobby', [
            'server' => $server,
            'lobby' => $lobby,
            'displayLobbyId' => $server->id,
            'isReady' => $isReady,
            'missingPlayers' => $missingPlayers,
            'isUnlimitedLobby' => $this->isUnlimitedLobby($server),
            'teamSize' => $teamSize,
            'ctCount' => $ctCount,
            'tCount' => $tCount,
            'currentTeam' => $currentTeam,
        ]);
    }

    public function status(Server $server): JsonResponse
    {
        $this->ensureBetaAccess($server);

        if (! $server->isOnline()) {
            return response()->json(['offline' => true], 409);
        }

        $this->updateHeartbeat($server, Auth::id());

        [$server, $lobby, $isReady, $missingPlayers] = $this->resolveLobbyState($server, Auth::id());
        
        // Ensure users are loaded with required fields
        $lobby->load('users:id,name,steam_nickname,avatar,rank_points');

        return response()->json($this->buildPayload($server, $lobby, $isReady, $missingPlayers));
    }

    public function heartbeat(Server $server): JsonResponse
    {
        $this->ensureBetaAccess($server);

        $this->updateHeartbeat($server, Auth::id());
        return response()->json(['ok' => true]);
    }

    private function updateHeartbeat(Server $server, int $userId): void
    {
        $lobby = $server->lobbies()
            ->whereIn('status', ['waiting', 'live'])
            ->whereHas('users', fn ($query) => $query->where('users.id', $userId))
            ->latest('id')
            ->first();

        if ($lobby) {
            \Illuminate\Support\Facades\Cache::put(
                "lobby:{$lobby->id}:user:{$userId}:heartbeat",
                now()->timestamp,
                60
            );
        }
    }

    public function leave(Server $server): JsonResponse
    {
        $this->ensureBetaAccess($server);

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

        if ($this->isLocked($lobby, $server)) {
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
        $isReady = ! $this->isUnlimitedLobby($server) && $lobby->status === 'live';
        $missingPlayers = $this->isUnlimitedLobby($server) ? 0 : max(0, $threshold - $lobby->users_count);

        $this->broadcastLobby($server, $lobby, $isReady, $missingPlayers);

        return response()->json(['left' => true]);
    }

    public function setTeam(Server $server): JsonResponse
    {
        $this->ensureBetaAccess($server);

        if ($this->isUnlimitedLobby($server)) {
            return response()->json(['message' => 'Los equipos no están habilitados en servidores públicos.'], 409);
        }

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

        if ($this->isLocked($lobby, $server)) {
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
        $isReady = ! $this->isUnlimitedLobby($server) && $lobby->status === 'live';
        $missingPlayers = $this->isUnlimitedLobby($server) ? 0 : max(0, $threshold - $lobby->users_count);

        $this->broadcastLobby($server, $lobby, $isReady, $missingPlayers);

        return response()->json($this->buildPayload($server, $lobby, $isReady, $missingPlayers));
    }

    public function toggleReady(Server $server): JsonResponse
    {
        $this->ensureBetaAccess($server);

        $userId = Auth::id();
        $lobby = $server->lobbies()
            ->whereIn('status', ['waiting', 'live'])
            ->whereHas('users', fn ($query) => $query->where('users.id', $userId))
            ->latest('id')
            ->first();

        if (! $lobby) {
            return response()->json(['message' => 'Lobby no encontrado'], 404);
        }

        if ($this->isLocked($lobby, $server)) {
            return response()->json(['message' => 'Match ya iniciado.'], 409);
        }

        $user = $lobby->users()->where('users.id', $userId)->first();
        $currentState = (bool) ($user->pivot?->is_ready ?? false);
        
        $lobby->users()->updateExistingPivot($userId, ['is_ready' => ! $currentState]);

        $lobby = $lobby->fresh()->load('users:id,name,steam_nickname,avatar,rank_points')->loadCount('users');
        
        $this->startMatchIfReady($server, $lobby);

        $server = $server->fresh();
        $threshold = $this->revealThreshold($server);
        $isReady = ! $this->isUnlimitedLobby($server) && $lobby->status === 'live';
        $missingPlayers = $this->isUnlimitedLobby($server) ? 0 : max(0, $threshold - $lobby->users_count);

        $this->broadcastLobby($server, $lobby, $isReady, $missingPlayers);

        return response()->json($this->buildPayload($server, $lobby, $isReady, $missingPlayers));
    }

    /**
     * @return array{Server, Lobby, bool, int}
     */
    private function resolveLobbyState(Server $server, int $userId): array
    {
        $this->cleanupInactiveLobbyUsers($server, $userId);

        $displayRequiredPlayers = min($server->max_players, 10);
        $revealThreshold = $this->revealThreshold($server);
        $shouldBroadcast = false;

        $lobby = $server->lobbies()
            ->orderByRaw("case when status in ('waiting', 'live') then 0 else 1 end")
            ->latest('id')
            ->first();

        if (! $lobby) {
            $lobby = $server->lobbies()->create([
                'status' => 'waiting',
                'name' => sprintf('Lobby %s #%s', $server->name, now()->format('His')),
                'required_players' => $displayRequiredPlayers,
            ]);
            $shouldBroadcast = true;
        } elseif ($lobby->status === 'completed') {
            // Reset existing lobby
            $lobby->users()->detach();
            $lobby->update([
                'status' => 'waiting',
                'name' => sprintf('Lobby %s #%s', $server->name, now()->format('His')),
                'required_players' => $displayRequiredPlayers,
                'started_at' => null,
            ]);
            $shouldBroadcast = true;
        }

        if ($lobby->required_players !== $displayRequiredPlayers) {
            $lobby->update(['required_players' => $displayRequiredPlayers]);
            $shouldBroadcast = true;
        }

        $alreadyInLobby = $lobby->users()->where('users.id', $userId)->exists();
        $currentCount = $lobby->users()->count();

        // Only allow joining if it's not locked or we are already in it
        $canJoinByCapacity = $this->isUnlimitedLobby($server) || $currentCount < $lobby->required_players;
        if (! $alreadyInLobby && $canJoinByCapacity && ! $this->isLocked($lobby, $server)) {
            $lobby->users()->syncWithoutDetaching([$userId]);
            $shouldBroadcast = true;
        }

        if (! $this->isUnlimitedLobby($server)) {
            $teamSize = $this->teamSize($lobby, $server);
            if ($this->ensureTeam($lobby, $server, $userId, $teamSize)) {
                $shouldBroadcast = true;
            }
        }

        $lobby->loadCount('users');
        
        $this->startMatchIfReady($server, $lobby);

        $this->syncServerPlayers($server);

        $lobby = $lobby->fresh()->load('users:id,name,steam_nickname,avatar,rank_points')->loadCount('users');
        $server = $server->fresh();

        $isReady = ! $this->isUnlimitedLobby($server) && $lobby->status === 'live';
        $missingPlayers = $this->isUnlimitedLobby($server)
            ? 0
            : max(0, $revealThreshold - $lobby->users_count);

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

        $password = null;
        if ($server->type === 'mm') {
            $match = LobbyMatch::query()->where('lobby_id', $lobby->id)->first();
            $password = $match?->config['password'] ?? null;
        }

        return [
            'server' => [
                'id' => $server->id,
                'name' => $server->name,
                'type' => $server->type,
                'ip' => $server->ip,
                'port' => $server->port,
                'password' => $password,
            ],
            'lobby' => [
                'id' => $server->id,
                'status' => $lobby->status,
                'is_unlimited' => $this->isUnlimitedLobby($server),
                'required_players' => $lobby->required_players,
                'users_count' => $lobby->users_count,
                'missing_players' => $missingPlayers,
                'team_size' => $teamSize,
                'ct_count' => $ctCount,
                't_count' => $tCount,
                'current_team' => $currentTeam,
                'locked' => $this->isLocked($lobby, $server),
            ],
            'is_ready' => $isReady,
            'users' => $lobby->users->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->steam_nickname ?? $user->name,
                'avatar' => $user->avatar,
                'rank_points' => $user->rank_points,
                'team' => $user->pivot?->team,
                'is_ready' => (bool) $user->pivot?->is_ready,
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

    private function ensureTeam(Lobby $lobby, Server $server, int $userId, int $teamSize): bool
    {
        if ($this->isLocked($lobby, $server)) {
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

    private function cleanupInactiveLobbyUsers(Server $server, ?int $skipUserId): bool
    {
        return app(LobbyHeartbeatCleaner::class)->cleanup($server, $skipUserId);
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

        abort(403, 'Matchmaking reservado para beta testers.');
    }

    private function isUnlimitedLobby(Server $server): bool
    {
        return $server->type === 'public';
    }

    private function isLocked(Lobby $lobby, Server $server): bool
    {
        if ($server->type === 'public') {
            return false;
        }

        return $lobby->status === 'live' && $lobby->started_at !== null;
    }

    private function revealThreshold(Server $server): int
    {
        if ($this->isUnlimitedLobby($server)) {
            return 0;
        }

        return 2;
    }

    private function startMatchIfReady(Server $server, Lobby $lobby): void
    {
        if ($server->type !== 'mm') {
            return;
        }

        if ($lobby->status === 'live' && $lobby->started_at !== null) {
            return;
        }

        $threshold = $this->revealThreshold($server);
        $playerCount = $lobby->users()->count();
        
        if ($playerCount < $threshold) {
            return;
        }

        // Check if everyone is ready
        $readyCount = $lobby->users()->wherePivot('is_ready', true)->count();
        if ($readyCount < $playerCount) {
            return;
        }

        // Check if teams are balanced (each team should have playerCount/2)
        $teamSize = intdiv($playerCount, 2);
        [$ctCount, $tCount] = $this->teamCounts($lobby);
        
        if ($ctCount !== $teamSize || $tCount !== $teamSize) {
            // Optional: You might want to allow start even if not perfectly balanced if it's not exactly 10 players
            // but for MM it's usually balanced.
            if ($playerCount === 10 && ($ctCount !== 5 || $tCount !== 5)) {
                return;
            }
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

        $rconHost = $server->ip;
        $rconPort = $server->port;
        $rconPassword = $server->rcon_password ?: (string) env('RCON_PASSWORD', '');

        if ($rconPassword === '') {
            return;
        }

        // Update lobby status to live/locked
        $lobby->update([
            'status' => 'live',
            'started_at' => now(),
        ]);

        $rcon = app(RconClient::class);
        // Configure the web API on the server first
        $rcon->send($rconHost, $rconPort, $rconPassword, sprintf('get5_web_api_url "%s/api/get5/events"', $baseUrl));
        $rcon->send($rconHost, $rconPort, $rconPassword, sprintf('get5_web_api_key "%s"', $token));

        // Identify this server in its requests to the website
        $rcon->send($rconHost, $rconPort, $rconPassword, 'get5_web_api_header_key "Get5-ServerId"');
        $rcon->send($rconHost, $rconPort, $rconPassword, sprintf('get5_web_api_header_value "%s"', $server->id));

        // Use separate databases for MM skins to avoid mixing with Public server
        $rcon->send($rconHost, $rconPort, $rconPassword, 'sm_weapons_db_connection "weapons_mm"');
        $rcon->send($rconHost, $rconPort, $rconPassword, 'sm_weapons_table_prefix "mm_"');

        // Temporarily disable skin commands for the duration of the match
        $rcon->send($rconHost, $rconPort, $rconPassword, 'sm_rename_command sm_ws sm_ws_disabled_mm');
        $rcon->send($rconHost, $rconPort, $rconPassword, 'sm_rename_command sm_gloves sm_gloves_disabled_mm');
        $rcon->send($rconHost, $rconPort, $rconPassword, 'sm_rename_command sm_agents sm_agents_disabled_mm');

        $url = $baseUrl . '/api/get5/match/' . $lobby->id;
        $command = sprintf(
            'get5_loadmatch_url "%s" "Authorization" "Bearer %s"',
            $url,
            $token
        );

        $rcon->send($rconHost, $rconPort, $rconPassword, $command);
    }
}
