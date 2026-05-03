<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Server;
use App\Models\User;
use App\Models\Lobby;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

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

    public function index(): View
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

        $displayServers = $servers->map(function (Server $server): array {
            return [
                'id' => $server->id,
                'name' => $server->name,
                'type' => $server->type,
                'status' => $server->status,
                'runtime_status' => $server->runtimeStatus(),
                'ip' => $server->ip,
                'port' => $server->port,
                'current_players' => $server->current_players,
                'max_players' => $server->max_players,
            ];
        })->all();

        $onlineServers = $servers->filter(fn (Server $server) => $server->runtimeStatus() === 'online')->count();

        return view('admin.index', [
            'users' => $users,
            'servers' => $displayServers,
            'totalUsers' => $totalUsers,
            'activeLobbies' => $activeLobbies,
            'totalServers' => $totalServers,
            'totalMatches' => $totalMatches,
            'onlineServers' => $onlineServers,
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

    public function deleteServer(Server $server): RedirectResponse
    {
        $server->delete();

        return back()->with('status', 'Servidor eliminado.');
    }

    public function clearLobbies(Request $request): RedirectResponse
    {
        $type = $request->string('type', 'mm');
        
        // Clear lobby_user for lobbies associated with the specified server type
        $query = \DB::table('lobby_user')
            ->join('lobbies', 'lobby_user.lobby_id', '=', 'lobbies.id')
            ->join('servers', 'lobbies.server_id', '=', 'servers.id')
            ->where('servers.type', $type);
            
        $clearedCount = $query->delete();
        
        // Reset player count on servers of the specified type
        Server::where('type', $type)->update(['current_players' => 0]);
        
        // Reset lobby status
        Lobby::whereHas('server', fn($q) => $q->where('type', $type))
            ->update(['status' => 'waiting', 'started_at' => null]);

        $msg = ($type === 'mm') ? "Matchmaking" : "Públicos";
        return back()->with('status', "Se han expulsado {$clearedCount} jugadores de los servidores {$msg}.");
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
            'status' => ['required', 'in:online,offline'],
            'max_players' => ['required', 'integer', 'min:1', 'max:64'],
            'current_players' => ['nullable', 'integer', 'min:0', 'max:64'],
            'rcon_password' => ['nullable', 'string', 'max:255'],
        ]);

        $data['current_players'] = $data['current_players'] ?? 0;
        $data['rcon_password'] = $data['rcon_password'] ?: null;

        return $data;
    }
}
