<?php

namespace App\Http\Controllers;

use App\Models\Server;
use App\Models\Lobby;
use App\Services\PterodactylService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class ServerController extends Controller
{
    /**
     * Muestra la vista principal de servidores.
     */
    public function index(PterodactylService $pterodactyl): View
    {
        $servers = $this->buildServersPayload($pterodactyl);

        return view('home', [
            'servers' => $servers,
        ]);
    }

    /**
     * Devuelve los datos de los servidores en formato JSON.
     * Actualiza el conteo de jugadores basado en los lobbies activos.
     */
    public function data(PterodactylService $pterodactyl): JsonResponse
    {
        return response()->json([
            'servers' => $this->buildServersPayload($pterodactyl),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildServersPayload(PterodactylService $pterodactyl): array
    {
        return Server::query()
            ->orderByRaw("case when type = 'public' then 0 when type = 'mm' then 1 else 2 end")
            ->orderBy('port')
            ->orderBy('name')
            ->get()
            ->map(function (Server $server) use ($pterodactyl): array {
                $hideAddress = $server->type === 'mm';
                $playersCount = $server->lobbies()
                    ->whereIn('status', ['waiting', 'live'])
                    ->get()
                    ->map(fn (Lobby $lobby) => $this->activeUsersCount($lobby))
                    ->max() ?? 0;

                if ($server->current_players !== $playersCount) {
                    $server->update(['current_players' => $playersCount]);
                    $server->current_players = $playersCount;
                }

                return [
                    'id' => $server->id,
                    'name' => $server->name,
                    'ip' => $hideAddress ? null : $server->ip,
                    'port' => $hideAddress ? null : $server->port,
                    'type' => $server->type,
                    'current_players' => $server->current_players,
                    'max_players' => $server->max_players,
                    'runtime_status' => $server->hasPterodactylIntegration()
                        ? (($server->pterodactyl_status === 'running') ? 'online' : ($server->pterodactyl_status ?: 'offline'))
                        : ($server->isOnline() ? 'online' : 'offline'),
                ];
            })
            ->all();
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
}
