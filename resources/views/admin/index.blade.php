@extends('layouts.app')

@section('title', 'Admin')

@section('content')
<div class="app-root min-h-screen bg-slate-950 text-slate-100 p-6 md:p-8">
    <div class="mx-auto max-w-6xl">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-black">Panel Admin</h1>
            <span class="rounded-md border border-slate-800 bg-slate-900 px-3 py-1 text-xs uppercase tracking-wide text-blue-300">Secreto</span>
        </div>

        @if(session('status'))
            <div class="mb-6 rounded-lg border border-blue-500/30 bg-blue-500/10 px-4 py-3 text-sm text-blue-100">
                {{ session('status') }}
            </div>
        @endif

        <div class="overflow-hidden rounded-xl border border-slate-800 bg-slate-900/80">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-800 text-slate-300 uppercase">
                    <tr>
                        <th class="p-4">Steam ID</th>
                        <th class="p-4">Nickname</th>
                        <th class="p-4">Rol</th>
                        <th class="p-4">Estado</th>
                        <th class="p-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @foreach($users as $user)
                        <tr class="hover:bg-slate-800/50">
                            <td class="p-4 font-mono text-xs text-slate-300">{{ $user->steam_id ?? 'N/A' }}</td>
                            <td class="p-4 font-semibold">{{ $user->steam_nickname ?? $user->name }}</td>
                            <td class="p-4">
                                <form method="POST" action="{{ route('admin.users.role', $user) }}" class="flex items-center gap-2">
                                    @csrf
                                    <select name="role" class="rounded-md border border-slate-700 bg-slate-950 px-2 py-1 text-xs text-slate-100">
                                        @foreach(['user' => 'User', 'store' => 'Store', 'admin' => 'Admin'] as $value => $label)
                                            <option value="{{ $value }}" @selected($user->role === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <button class="rounded-md bg-blue-600 px-3 py-1 text-xs font-semibold text-white hover:bg-blue-500">Guardar</button>
                                </form>
                            </td>
                            <td class="p-4">
                                <span class="rounded-md px-2 py-1 text-xs {{ $user->banned_at ? 'bg-red-500/20 text-red-200' : 'bg-emerald-500/20 text-emerald-200' }}">
                                    {{ $user->banned_at ? 'Baneado' : 'Activo' }}
                                </span>
                            </td>
                            <td class="p-4 text-right">
                                <form method="POST" action="{{ route('admin.users.ban', $user) }}">
                                    @csrf
                                    <button class="rounded-md border border-slate-700 px-3 py-1 text-xs font-semibold text-slate-200 hover:border-slate-500">
                                        {{ $user->banned_at ? 'Desbanear' : 'Banear' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
