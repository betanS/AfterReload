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
        $allowed = ['user', 'store', 'admin'];

        if (! in_array($role, $allowed, true)) {
            return back()->with('status', 'Rol no valido.');
        }

        $user->update(['role' => $role]);

        return back()->with('status', 'Rol actualizado.');
    }

    public function toggleBan(User $user): RedirectResponse
    {
        $user->update([
            'banned_at' => $user->banned_at ? null : now(),
        ]);

        return back()->with('status', $user->banned_at ? 'Usuario desbloqueado.' : 'Usuario bloqueado.');
    }
}
