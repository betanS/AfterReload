<?php

namespace App\Http\Controllers\Lobby;

use App\Http\Controllers\Controller;
use App\Models\Lobby;
use App\Models\Server;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LobbyController extends Controller
{
    public function show(Server $server): View
    {
        if (! $server->isOnline()) {
            return redirect()->route('home')->with('server_error', 'Servidor offline. Intenta mas tarde.');
        }

        [$server, $lobby, $isReady, $missingPlayers] = $this->resolveLobbyState($server, Auth::id());

        return view('lobby', [
            'server' => $server,
            'lobby' => $lobby,
            'isReady' => $isReady,
            'missingPlayers' => $missingPlayers,
        ]);
    }

    public function status(Server $server): JsonResponse
    {
        if (! $server->isOnline()) {
            return response()->json(['offline' => true], 409);
        }

        [$server, $lobby, $isReady, $missingPlayers] = $this->resolveLobbyState($server, Auth::id());

        return response()->json([
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
            ],
            'is_ready' => $isReady,
            'users' => $lobby->users->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->steam_nickname ?? $user->name,
                'avatar' => $user->avatar,
                'rank_points' => $user->rank_points,
            ])->values(),
        ]);
    }

    /**
     * @return array{Server, Lobby, bool, int}
     */
    private function resolveLobbyState(Server $server, int $userId): array
    {
        $displayRequiredPlayers = min($server->max_players, 10);
        $revealThreshold = 1;

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
        }

        if ($lobby->required_players !== $displayRequiredPlayers) {
            $lobby->update(['required_players' => $displayRequiredPlayers]);
        }

        $alreadyInLobby = $lobby->users()->where('users.id', $userId)->exists();
        $currentCount = $lobby->users()->count();

        if (! $alreadyInLobby && $currentCount < $lobby->required_players) {
            $lobby->users()->syncWithoutDetaching([$userId]);
        }

        $lobby->loadCount('users');
        $playerCount = $lobby->users_count;

        if ($playerCount >= $revealThreshold && $lobby->status === 'waiting') {
            $lobby->update([
                'status' => 'live',
                'started_at' => now(),
            ]);
        }

        $server->update([
            'current_players' => $playerCount,
        ]);

        $lobby = $lobby->fresh()->load('users:id,name,steam_nickname,avatar,rank_points')->loadCount('users');
        $server = $server->fresh();

        $isReady = $lobby->users_count >= $revealThreshold;
        $missingPlayers = max(0, $revealThreshold - $lobby->users_count);

        return [$server, $lobby, $isReady, $missingPlayers];
    }
}
