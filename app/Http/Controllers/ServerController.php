<?php

namespace App\Http\Controllers;

use App\Models\Server;
use App\Models\Lobby;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class ServerController extends Controller
{
    /**
     * Muestra la vista principal de servidores.
     */
    public function index(): View
    {
        $servers = $this->buildServersPayload();

        return view('home', [
            'servers' => $servers,
        ]);
    }

    /**
     * Devuelve los datos de los servidores en formato JSON.
     * Actualiza el conteo de jugadores basado en los lobbies activos.
     */
    public function data(): JsonResponse
    {
        return response()->json([
            'servers' => $this->buildServersPayload(),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildServersPayload(): array
    {
        return Server::query()
            ->orderByRaw("case when type = 'public' then 0 when type = 'mm' then 1 else 2 end")
            ->orderBy('port')
            ->orderBy('name')
            ->get()
            ->map(function (Server $server): array {
                $hideAddress = $server->type === 'mm';
                $playersCount = $server->lobbies()
                    ->whereIn('status', ['waiting', 'live'])
                    ->get()
                    ->map(fn (Lobby $lobby) => $lobby->users()->count())
                    ->max() ?? 0;

                if ($server->current_players !== $playersCount) {
                    $server->update(['current_players' => $playersCount]);
                    $server->current_players = $playersCount;
                }

                return [
                    'id' => $server->id,
                    'name' => $server->name,
                    'ip' => null,
                    'port' => null,
                    'type' => $server->type,
                    'current_players' => $server->current_players,
                    'max_players' => $server->max_players,
                    'runtime_status' => $server->runtimeStatus(),
                ];
            })
            ->all();
    }
}
