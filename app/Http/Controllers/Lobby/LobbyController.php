<?php

namespace App\Http\Controllers\Lobby;

use App\Http\Controllers\Controller;
use App\Models\Lobby;
use App\Models\Server;
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
                'name' => $user->name,
                'avatar' => $user->avatar,
                'steam_id' => $user->steam_id,
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

        $existingLobby = $server->lobbies()
            ->whereIn('status', ['waiting', 'live'])
            ->whereHas('users', fn ($query) => $query->where('users.id', $userId))
            ->latest('id')
            ->first();

        $lobby = $existingLobby ?: $server->lobbies()->firstOrCreate(
            ['status' => 'waiting'],
            [
                'name' => sprintf('Lobby %s #%s', $server->name, now()->format('His')),
                'required_players' => $displayRequiredPlayers,
            ]
        );

        if ($lobby->required_players !== $displayRequiredPlayers) {
            $lobby->update(['required_players' => $displayRequiredPlayers]);
        }

        if (! $lobby->users()->where('users.id', $userId)->exists()) {
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

        $lobby = $lobby->fresh()->load('users:id,name,avatar,steam_id')->loadCount('users');
        $server = $server->fresh();

        $isReady = $lobby->users_count >= $revealThreshold;
        $missingPlayers = max(0, $revealThreshold - $lobby->users_count);

        return [$server, $lobby, $isReady, $missingPlayers];
    }
}