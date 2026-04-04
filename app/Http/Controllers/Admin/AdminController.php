<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function index(): View
    {
        $users = User::query()
            ->orderByDesc('created_at')
            ->get();

        return view('admin.index', [
            'users' => $users,
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
}
