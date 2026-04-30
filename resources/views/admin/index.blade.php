@extends('layouts.app')

@section('title', __('Admin Panel'))

@section('content')
<div class="app-root min-h-screen bg-[#121212] text-slate-100 p-8">
    <div class="max-w-7xl mx-auto">
        @if (session('status'))
            <div class="mb-6 rounded-sm border border-[#5b7cff]/30 bg-[#5b7cff]/10 px-4 py-3 text-sm font-bold text-[#a5b4ff]">
                {{ session('status') }}
            </div>
        @endif

        <div class="mb-10">
            <h2 class="text-4xl font-black uppercase tracking-tighter text-white italic">{{ __('Panel de Administración') }}</h2>
            <p class="text-sm text-slate-500 uppercase font-bold tracking-widest mt-2">{{ __('Gestión global de usuarios y plataforma') }}</p>
        </div>

        <!-- Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-12">
            <div class="bg-[#1b1b1b] border border-[#222222] p-6 rounded-sm shadow-xl">
                <p class="text-[10px] uppercase font-bold text-slate-500 tracking-widest mb-2">{{ __('Total Usuarios') }}</p>
                <p class="text-3xl font-black text-white italic italic">{{ $totalUsers }}</p>
            </div>
            <div class="bg-[#1b1b1b] border border-[#222222] p-6 rounded-sm shadow-xl border-l-4 border-[#5b7cff]">
                <p class="text-[10px] uppercase font-bold text-slate-500 tracking-widest mb-2">{{ __('Lobbies Activos') }}</p>
                <p class="text-3xl font-black text-white italic italic">{{ $activeLobbies }}</p>
            </div>
            <div class="bg-[#1b1b1b] border border-[#222222] p-6 rounded-sm shadow-xl">
                <p class="text-[10px] uppercase font-bold text-slate-500 tracking-widest mb-2">{{ __('Servidores') }}</p>
                <p class="text-3xl font-black text-white italic italic">{{ $totalServers }}</p>
            </div>
            <div class="bg-[#1b1b1b] border border-[#222222] p-6 rounded-sm shadow-xl">
                <p class="text-[10px] uppercase font-bold text-slate-500 tracking-widest mb-2">{{ __('Partidas Totales') }}</p>
                <p class="text-3xl font-black text-white italic italic">{{ $totalMatches }}</p>
            </div>
            <div class="bg-[#1b1b1b] border border-[#222222] p-6 rounded-sm shadow-xl">
                <p class="text-[10px] uppercase font-bold text-slate-500 tracking-widest mb-2">{{ __('Servidores Online') }}</p>
                <p class="text-3xl font-black text-white italic italic">{{ $onlineServers }}</p>
            </div>
        </div>

        <div class="mb-12 rounded-sm border border-[#222222] bg-[#1b1b1b] shadow-2xl overflow-hidden">
            <div class="flex flex-col gap-4 border-b border-[#222222] bg-[#1b1b1b] p-6 md:flex-row md:items-start md:justify-between">
                <div>
                    <h3 class="font-black uppercase tracking-widest text-white text-sm italic">{{ __('Servidores y Pterodactyl') }}</h3>
                    <p class="mt-2 text-xs uppercase tracking-[0.2em] text-slate-500">{{ __('La administración real vive en panel.afterreload; aquí solo queda el resumen operativo.') }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    @if ($pterodactylConfigured && $pterodactylPanelUrl !== '')
                        <form action="{{ route('admin.servers.import') }}" method="POST">
                            @csrf
                            <button type="submit" class="inline-flex items-center justify-center rounded-sm border border-emerald-500 bg-emerald-500/10 px-4 py-3 text-[10px] font-black uppercase tracking-widest text-emerald-300 transition hover:bg-emerald-500 hover:text-white">
                                {{ __('Importar servers de Pterodactyl') }}
                            </button>
                        </form>
                        <a href="{{ $pterodactylPanelUrl }}" target="_blank" rel="noreferrer" class="inline-flex items-center justify-center rounded-sm border border-[#5b7cff] bg-[#5b7cff] px-4 py-3 text-[10px] font-black uppercase tracking-widest text-white transition hover:bg-[#7c5cff]">
                            {{ __('Abrir panel.afterreload') }}
                        </a>
                    @endif
                </div>
            </div>

            <div class="grid gap-4 border-b border-[#222222] bg-[#161616] p-6 md:grid-cols-3">
                <div class="rounded-sm border border-[#222222] bg-[#121212] p-4">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500">{{ __('Fuente de la lista') }}</p>
                    <p class="mt-2 text-sm font-black uppercase tracking-wide text-white">{{ __('Base local') }}</p>
                    <p class="mt-2 text-xs text-slate-500">{{ __('El dashboard muestra los servidores registrados en la web y deja Pterodactyl solo como acceso externo.') }}</p>
                </div>
                <div class="rounded-sm border border-[#222222] bg-[#121212] p-4">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500">{{ __('Panel externo') }}</p>
                    <p class="mt-2 text-sm font-black uppercase tracking-wide {{ $pterodactylPanelUrl !== '' ? 'text-emerald-400' : 'text-red-400' }}">
                        {{ $pterodactylPanelUrl !== '' ? __('Disponible') : __('Sin configurar') }}
                    </p>
                    <p class="mt-2 break-all text-xs text-slate-500">{{ $pterodactylPanelUrl !== '' ? $pterodactylPanelUrl : __('Falta PTERODACTYL_URL.') }}</p>
                </div>
                <div class="rounded-sm border border-[#222222] bg-[#121212] p-4">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500">{{ __('Uso de Pterodactyl') }}</p>
                    <p class="mt-2 text-sm font-black uppercase tracking-wide text-white">{{ __('Gestión externa') }}</p>
                    <p class="mt-2 text-xs text-slate-500">{{ __('La administración directa sigue en panel.afterreload; aquí solo quedan el resumen y los accesos rápidos.') }}</p>
                </div>
            </div>

            <div class="grid gap-4 p-6">
                @forelse ($servers as $server)
                    @php
                        $runtimeStatus = $server['runtime_status'];
                        $panelLink = $server['panel_link'] ?? null;
                    @endphp
                    <div class="rounded-sm border border-[#222222] bg-[#161616] p-5">
                        <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                            <div>
                                <div class="flex items-center gap-3">
                                    <h4 class="text-lg font-black uppercase tracking-tight text-white">{{ $server['name'] }}</h4>
                                    <span class="rounded-sm border px-2 py-1 text-[9px] font-black uppercase tracking-widest {{ in_array($runtimeStatus, ['online', 'running'], true) ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-400' : 'border-slate-700 bg-slate-900/60 text-slate-400' }}">
                                        {{ strtoupper($runtimeStatus) }}
                                    </span>
                                    <span class="rounded-sm border border-[#5b7cff]/30 bg-[#5b7cff]/10 px-2 py-1 text-[9px] font-black uppercase tracking-widest text-[#a5b4ff]">
                                        {{ strtoupper($server['type'] ?? 'server') }}
                                    </span>
                                </div>
                                <p class="mt-2 text-xs text-slate-500">
                                    @if (!empty($server['ip']) && !empty($server['port']))
                                        {{ $server['ip'] }}:{{ $server['port'] }}
                                    @endif
                                    @if (!empty($server['identifier']))
                                        <span class="ml-3 font-mono text-[#a5b4ff]">Ptero: {{ $server['identifier'] }}</span>
                                    @endif
                                </p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                @if ($panelLink)
                                    <a href="{{ $panelLink }}" target="_blank" rel="noreferrer" class="rounded-sm border border-[#5b7cff]/40 px-3 py-2 text-[10px] font-black uppercase tracking-widest text-[#a5b4ff] transition hover:bg-[#5b7cff] hover:text-white">
                                        {{ __('Gestionar en panel.afterreload') }}
                                    </a>
                                @elseif ($pterodactylPanelUrl !== '')
                                    <a href="{{ $pterodactylPanelUrl }}" target="_blank" rel="noreferrer" class="rounded-sm border border-slate-600 px-3 py-2 text-[10px] font-black uppercase tracking-widest text-slate-300 transition hover:border-white hover:text-white">
                                        {{ __('Abrir panel general') }}
                                    </a>
                                @endif
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                            <div class="rounded-sm border border-[#222222] bg-[#121212] px-4 py-3">
                                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500">{{ __('Host') }}</p>
                                <p class="mt-2 text-sm font-bold text-white">{{ $server['ip'] ?? __('N/D') }}</p>
                            </div>
                            <div class="rounded-sm border border-[#222222] bg-[#121212] px-4 py-3">
                                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500">{{ __('Puerto') }}</p>
                                <p class="mt-2 text-sm font-bold text-white">{{ $server['port'] ?? __('N/D') }}</p>
                            </div>
                            <div class="rounded-sm border border-[#222222] bg-[#121212] px-4 py-3">
                                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500">{{ __('Jugadores') }}</p>
                                <p class="mt-2 text-sm font-bold text-white">
                                    @if (($server['current_players'] ?? null) !== null && ($server['max_players'] ?? null) !== null)
                                        {{ $server['current_players'] }} / {{ $server['max_players'] }}
                                    @else
                                        {{ __('N/D') }}
                                    @endif
                                </p>
                            </div>
                            <div class="rounded-sm border border-[#222222] bg-[#121212] px-4 py-3">
                                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500">{{ __('Última sincronización') }}</p>
                                <p class="mt-2 text-sm font-bold text-white">{{ $server['last_synced_human'] ?? __('Nunca') }}</p>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="rounded-sm border border-dashed border-[#333333] p-6 text-sm text-slate-500">
                        {{ __('No hay servidores configurados todavía.') }}
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Users Table -->
        <div class="rounded-sm border border-[#222222] bg-[#1b1b1b] shadow-2xl overflow-hidden">
            <div class="p-6 border-b border-[#222222] flex justify-between items-center bg-[#1b1b1b]">
                <h3 class="font-black uppercase tracking-widest text-white text-sm italic">{{ __('Usuarios Registrados') }}</h3>
                <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">{{ __('Lista de jugadores') }}</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-[#222222] text-slate-400 uppercase font-bold tracking-widest text-[10px] border-b border-[#333333]">
                        <tr>
                            <th class="p-4">{{ __('ID / Steam') }}</th>
                            <th class="p-4">{{ __('Jugador') }}</th>
                            <th class="p-4">{{ __('Rol') }}</th>
                            <th class="p-4">{{ __('Estado') }}</th>
                            <th class="p-4 text-right">{{ __('Acciones') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#222222]">
                        @foreach($users as $user)
                            <tr class="transition hover:bg-[#222222]/40 group">
                                <td class="p-4">
                                    <p class="text-xs text-slate-500 font-bold uppercase tracking-widest">ID: {{ $user->id }}</p>
                                    <p class="text-[10px] text-[#5b7cff] font-mono mt-0.5">{{ $user->steam_id }}</p>
                                </td>
                                <td class="p-4">
                                    <div class="flex items-center gap-3">
                                        <img src="{{ $user->avatar }}" alt="Avatar" class="h-8 w-8 rounded-sm border border-[#222222]">
                                        <div class="leading-tight">
                                            <p class="font-bold text-white">{{ $user->steam_nickname ?? $user->name }}</p>
                                            <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest mt-0.5">{{ $user->rank_points }} RP</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-4">
                                    <form action="{{ route('admin.users.role', $user) }}" method="POST" class="inline-block">
                                        @csrf
                                        <select name="role" onchange="this.form.submit()" class="bg-[#121212] border border-[#333333] rounded-sm px-2 py-1 text-[10px] font-black uppercase tracking-widest text-slate-300 focus:border-[#5b7cff] outline-none">
                                            <option value="user" {{ $user->role === 'user' ? 'selected' : '' }}>{{ __('User') }}</option>
                                            <option value="betatester" {{ $user->role === 'betatester' ? 'selected' : '' }}>{{ __('Beta Tester') }}</option>
                                            <option value="store" {{ $user->role === 'store' ? 'selected' : '' }}>{{ __('Store Manager') }}</option>
                                            <option value="admin" {{ $user->role === 'admin' ? 'selected' : '' }}>{{ __('Admin') }}</option>
                                        </select>
                                    </form>
                                </td>
                                <td class="p-4">
                                    @if($user->banned_at)
                                        <span class="px-2 py-1 rounded-sm bg-red-500/10 border border-red-500/20 text-[9px] font-black uppercase tracking-widest text-red-500">
                                            {{ __('Baneado') }}
                                        </span>
                                    @else
                                        <span class="px-2 py-1 rounded-sm bg-emerald-500/10 border border-emerald-500/20 text-[9px] font-black uppercase tracking-widest text-emerald-500">
                                            {{ __('Activo') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="p-4 text-right">
                                    @if($user->banned_at)
                                        <form action="{{ route('admin.users.unban', $user) }}" method="POST" class="inline-block">
                                            @csrf
                                            <button type="submit" class="rounded-sm border border-emerald-500 bg-emerald-500/5 px-3 py-1.5 text-[9px] font-black uppercase tracking-widest text-emerald-400 hover:bg-emerald-500 hover:text-white transition">
                                                {{ __('Desbanear') }}
                                            </button>
                                        </form>
                                    @else
                                        <form action="{{ route('admin.users.ban', $user) }}" method="POST" class="inline-block">
                                            @csrf
                                            <button type="submit" class="rounded-sm border border-red-500 bg-red-500/5 px-3 py-1.5 text-[9px] font-black uppercase tracking-widest text-red-400 hover:bg-red-500 hover:text-white transition">
                                                {{ __('Banear') }}
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-4 bg-[#222222]/30 border-t border-[#222222]">
                {{ $users->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
