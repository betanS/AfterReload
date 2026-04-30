<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Server;
use App\Models\User;
use App\Services\PterodactylService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = $request->user();

            if (! $user || ! $user->isAdmin()) {
                abort(403);
            }

            return $next($request);
        });
    }

    public function index(PterodactylService $pterodactyl): View
    {
        $users = User::query()
            ->orderByDesc('created_at')
            ->paginate(50);

        $totalUsers = User::count();
        $activeLobbies = \App\Models\Lobby::where('status', 'waiting')->count();
        $totalServers = \App\Models\Server::count();
        $totalMatches = \App\Models\MatchResult::count();
        $servers = Server::query()
            ->orderByRaw("case when type = 'public' then 0 when type = 'mm' then 1 else 2 end")
            ->orderBy('port')
            ->orderBy('name')
            ->get();
        $displayServers = $servers->map(function (Server $server) use ($pterodactyl): array {
            return [
                'name' => $server->name,
                'type' => $server->type,
                'runtime_status' => $server->runtimeStatus(),
                'ip' => $server->ip,
                'port' => $server->port,
                'current_players' => $server->current_players,
                'max_players' => $server->max_players,
                'identifier' => $server->pterodactyl_identifier,
                'panel_link' => $server->hasPterodactylIntegration() ? $pterodactyl->panelLink($server) : null,
                'last_synced_human' => optional($server->pterodactyl_last_synced_at)->diffForHumans() ?? __('Nunca'),
            ];
        })->all();
        $onlineServers = $servers->filter(fn (Server $server) => $server->runtimeStatus() === 'running' || $server->runtimeStatus() === 'online')->count();

        return view('admin.index', [
            'users' => $users,
            'servers' => $displayServers,
            'totalUsers' => $totalUsers,
            'activeLobbies' => $activeLobbies,
            'totalServers' => $totalServers,
            'totalMatches' => $totalMatches,
            'onlineServers' => $onlineServers,
            'pterodactylConfigured' => $pterodactyl->isConfigured(),
            'pterodactylPanelUrl' => $pterodactyl->getPanelUrl(),
        ]);
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $role = $request->string('role')->trim()->lower()->value();
        $allowed = ['user', 'store', 'admin', 'betatester'];

        if (! in_array($role, $allowed, true)) {
            return back()->with('status', 'Rol no valido.');
        }

        $user->update(['role' => $role]);

        return back()->with('status', 'Rol actualizado.');
    }

    public function unban(User $user): RedirectResponse
    {
        if (! $user->banned_at) {
            return back()->with('status', 'El usuario ya estaba activo.');
        }

        $user->update(['banned_at' => null]);

        return back()->with('status', 'Usuario desbloqueado.');
    }

    public function toggleBan(User $user): RedirectResponse
    {
        $wasBanned = $user->banned_at !== null;

        $user->update([
            'banned_at' => $wasBanned ? null : now(),
        ]);

        return back()->with('status', $wasBanned ? 'Usuario desbloqueado.' : 'Usuario bloqueado.');
    }

    public function storeServer(Request $request): RedirectResponse
    {
        Server::query()->create($this->validatedServerData($request));

        return back()->with('status', 'Servidor creado.');
    }

    public function updateServer(Request $request, Server $server): RedirectResponse
    {
        $server->update($this->validatedServerData($request));

        return back()->with('status', 'Servidor actualizado.');
    }

    public function syncServer(Server $server, PterodactylService $pterodactyl): RedirectResponse
    {
        try {
            $pterodactyl->sync($server);
        } catch (\Throwable $e) {
            return back()->with('status', 'Error al sincronizar Pterodactyl: ' . $e->getMessage());
        }

        return back()->with('status', 'Servidor sincronizado con Pterodactyl.');
    }

    public function importServers(PterodactylService $pterodactyl): RedirectResponse
    {
        if (! $pterodactyl->hasApplicationApi()) {
            return back()->with('status', 'Para importar necesitas PTERODACTYL_URL y PTERODACTYL_APPLICATION_API_KEY.');
        }

        try {
            $remoteServers = $pterodactyl->listServers();
        } catch (\Throwable $e) {
            return back()->with('status', 'Error al importar desde Pterodactyl: ' . $e->getMessage());
        }

        $imported = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($remoteServers as $remote) {
            $identifier = trim((string) Arr::get($remote, 'identifier', ''));
            $name = trim((string) Arr::get($remote, 'name', ''));
            $ip = Arr::get($remote, 'ip');
            $port = Arr::get($remote, 'port');

            if ($identifier === '' || $name === '' || ! is_string($ip) || $ip === '' || ! is_numeric($port)) {
                $skipped++;
                continue;
            }

            $status = Arr::get($remote, 'status');
            $statusValue = is_string($status) && $status !== '' ? $status : null;

            $server = Server::query()->where('pterodactyl_identifier', $identifier)->first();

            if (! $server) {
                $server = Server::query()->where('ip', $ip)->where('port', (int) $port)->first();
            }

            if ($server) {
                $payload = [
                    'name' => $name,
                    'ip' => $ip,
                    'port' => (int) $port,
                    'type' => $server->type ?: 'mm',
                    'max_players' => $server->max_players > 0 ? $server->max_players : 10,
                    'pterodactyl_identifier' => $identifier,
                    'pterodactyl_uuid' => Arr::get($remote, 'uuid'),
                    'pterodactyl_status' => $statusValue,
                    'pterodactyl_last_synced_at' => now(),
                ];
                $server->update($payload);
                $updated++;
            } else {
                Server::query()->create([
                    'name' => $name,
                    'ip' => $ip,
                    'port' => (int) $port,
                    'type' => 'mm',
                    'max_players' => 10,
                    'current_players' => 0,
                    'rcon_password' => null,
                    'pterodactyl_identifier' => $identifier,
                    'pterodactyl_uuid' => Arr::get($remote, 'uuid'),
                    'pterodactyl_status' => $statusValue,
                    'pterodactyl_last_synced_at' => now(),
                ]);
                $imported++;
            }
        }

        return back()->with('status', "Import completado. Nuevos: {$imported}, actualizados: {$updated}, omitidos: {$skipped}.");
    }

    public function powerServer(Request $request, Server $server, PterodactylService $pterodactyl): RedirectResponse
    {
        $signal = $request->string('signal')->lower()->value();

        try {
            $pterodactyl->sendPowerSignal($server, $signal);
            $pterodactyl->sync($server);
        } catch (\Throwable $e) {
            return back()->with('status', 'Error en acción de energía: ' . $e->getMessage());
        }

        return back()->with('status', 'Acción enviada al panel: ' . $signal . '.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedServerData(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'ip' => ['required', 'string', 'max:255'],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
            'type' => ['required', 'in:public,mm'],
            'max_players' => ['required', 'integer', 'min:1', 'max:64'],
            'current_players' => ['nullable', 'integer', 'min:0', 'max:64'],
            'rcon_password' => ['nullable', 'string', 'max:255'],
            'pterodactyl_identifier' => ['nullable', 'string', 'max:255'],
        ]);

        $data['current_players'] = $data['current_players'] ?? 0;
        $data['rcon_password'] = $data['rcon_password'] ?: null;
        $data['pterodactyl_identifier'] = $data['pterodactyl_identifier'] ?: null;

        if (! $data['pterodactyl_identifier']) {
            $data['pterodactyl_uuid'] = null;
            $data['pterodactyl_status'] = null;
            $data['pterodactyl_last_synced_at'] = null;
        }

        return $data;
    }
}
