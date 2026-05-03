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

        <div class="mb-10 flex justify-between items-end">
            <div>
                <h2 class="text-4xl font-black uppercase tracking-tighter text-white italic">{{ __('Panel de Administración') }}</h2>
                <p class="text-sm text-slate-500 uppercase font-bold tracking-widest mt-2">{{ __('Gestión global de usuarios y plataforma') }}</p>
            </div>
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

        <!-- Servers Management -->
        <div class="mb-12 rounded-sm border border-[#222222] bg-[#1b1b1b] shadow-2xl overflow-hidden">
            <div class="flex flex-col gap-4 border-b border-[#222222] bg-[#1b1b1b] p-6 md:flex-row md:items-center md:justify-between">
                <div>
                    <h3 class="font-black uppercase tracking-widest text-white text-sm italic">{{ __('Gestión de Servidores') }}</h3>
                    <p class="mt-2 text-xs uppercase tracking-[0.2em] text-slate-500">{{ __('Control operativo de los nodos de juego.') }}</p>
                </div>
                <div class="flex gap-3">
                    <form action="{{ route('admin.servers.clear-lobbies', ['type' => 'mm']) }}" method="POST" onsubmit="return confirm('¿Seguro que quieres limpiar todos los lobbies de Matchmaking?')">
                        @csrf
                        <button type="submit" class="inline-flex items-center justify-center rounded-sm border border-red-500/50 bg-red-500/10 px-4 py-3 text-[10px] font-black uppercase tracking-widest text-red-400 transition hover:bg-red-500 hover:text-white">
                            {{ __('Limpiar MM') }}
                        </button>
                    </form>
                    <form action="{{ route('admin.servers.clear-lobbies', ['type' => 'public']) }}" method="POST" onsubmit="return confirm('¿Seguro que quieres limpiar todos los servidores Públicos?')">
                        @csrf
                        <button type="submit" class="inline-flex items-center justify-center rounded-sm border border-emerald-500/50 bg-emerald-500/10 px-4 py-3 text-[10px] font-black uppercase tracking-widest text-emerald-400 transition hover:bg-emerald-500 hover:text-white">
                            {{ __('Limpiar Públicos') }}
                        </button>
                    </form>
                    <button onclick="document.getElementById('server-modal-create').classList.remove('hidden')" class="inline-flex items-center justify-center rounded-sm border border-[#5b7cff] bg-[#5b7cff] px-4 py-3 text-[10px] font-black uppercase tracking-widest text-white transition hover:bg-[#7c5cff]">
                        {{ __('Añadir Servidor') }}
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-[#222222] text-slate-400 uppercase font-bold tracking-widest text-[10px] border-b border-[#333333]">
                        <tr>
                            <th class="p-4">{{ __('Servidor') }}</th>
                            <th class="p-4">{{ __('Dirección') }}</th>
                            <th class="p-4">{{ __('Tipo') }}</th>
                            <th class="p-4">{{ __('Config') }}</th>
                            <th class="p-4">{{ __('Estado Real') }}</th>
                            <th class="p-4">{{ __('Jugadores') }}</th>
                            <th class="p-4 text-right">{{ __('Acciones') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#222222]">
                        @foreach($servers as $server)
                            <tr class="transition hover:bg-[#222222]/40 group">
                                <td class="p-4">
                                    <p class="font-bold text-white">{{ $server['name'] }}</p>
                                </td>
                                <td class="p-4">
                                    <p class="text-[10px] font-mono text-slate-400">{{ $server['ip'] }}:{{ $server['port'] }}</p>
                                </td>
                                <td class="p-4">
                                    <span class="rounded-sm border border-[#5b7cff]/30 bg-[#5b7cff]/10 px-2 py-1 text-[9px] font-black uppercase tracking-widest text-[#a5b4ff]">
                                        {{ strtoupper($server['type']) }}
                                    </span>
                                </td>
                                <td class="p-4">
                                    <span class="text-[9px] font-black uppercase tracking-widest {{ $server['status'] === 'online' ? 'text-emerald-400' : 'text-red-400' }}">
                                        {{ strtoupper($server['status']) }}
                                    </span>
                                </td>
                                <td class="p-4">
                                    <span class="rounded-sm border px-2 py-1 text-[9px] font-black uppercase tracking-widest {{ $server['runtime_status'] === 'online' ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-400' : 'border-slate-700 bg-slate-900/60 text-slate-400' }}">
                                        {{ strtoupper($server['runtime_status']) }}
                                    </span>
                                </td>
                                <td class="p-4">
                                    <p class="text-xs font-bold text-white">{{ $server['current_players'] }} / {{ $server['max_players'] }}</p>
                                </td>
                                <td class="p-4 text-right flex justify-end gap-2">
                                    <button onclick="editServer({{ json_encode($server) }})" class="rounded-sm border border-slate-600 px-3 py-1.5 text-[9px] font-black uppercase tracking-widest text-slate-300 hover:border-white hover:text-white transition">
                                        {{ __('Editar') }}
                                    </button>
                                    <form action="{{ route('admin.servers.delete', $server['id']) }}" method="POST" onsubmit="return confirm('¿Eliminar servidor?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-sm border border-red-500/50 bg-red-500/5 px-3 py-1.5 text-[9px] font-black uppercase tracking-widest text-red-400 hover:bg-red-500 hover:text-white transition">
                                            {{ __('Borrar') }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
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

<!-- Create Server Modal -->
<div id="server-modal-create" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 hidden">
    <div class="w-full max-w-md bg-[#1b1b1b] border border-[#222222] p-8 rounded-sm shadow-2xl">
        <h3 class="text-2xl font-black uppercase tracking-tighter text-white italic mb-6">{{ __('Añadir Servidor') }}</h3>
        <form action="{{ route('admin.servers.store') }}" method="POST">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-2">{{ __('Nombre') }}</label>
                    <input type="text" name="name" required class="w-full bg-[#121212] border border-[#222222] rounded-sm px-4 py-2 text-sm text-white focus:border-[#5b7cff] outline-none">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-2">{{ __('IP') }}</label>
                        <input type="text" name="ip" value="127.0.0.1" required class="w-full bg-[#121212] border border-[#222222] rounded-sm px-4 py-2 text-sm text-white focus:border-[#5b7cff] outline-none">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-2">{{ __('Puerto') }}</label>
                        <input type="number" name="port" required class="w-full bg-[#121212] border border-[#222222] rounded-sm px-4 py-2 text-sm text-white focus:border-[#5b7cff] outline-none">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-2">{{ __('Tipo') }}</label>
                        <select name="type" class="w-full bg-[#121212] border border-[#222222] rounded-sm px-4 py-2 text-sm text-white focus:border-[#5b7cff] outline-none">
                            <option value="mm">Matchmaking</option>
                            <option value="public">Público</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-2">{{ __('Estado') }}</label>
                        <select name="status" class="w-full bg-[#121212] border border-[#222222] rounded-sm px-4 py-2 text-sm text-white focus:border-[#5b7cff] outline-none">
                            <option value="online">Online</option>
                            <option value="offline">Offline</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-2">{{ __('Máx. Jugadores') }}</label>
                        <input type="number" name="max_players" value="10" required class="w-full bg-[#121212] border border-[#222222] rounded-sm px-4 py-2 text-sm text-white focus:border-[#5b7cff] outline-none">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-2">{{ __('RCON Password') }}</label>
                        <input type="password" name="rcon_password" class="w-full bg-[#121212] border border-[#222222] rounded-sm px-4 py-2 text-sm text-white focus:border-[#5b7cff] outline-none">
                    </div>
                </div>
            </div>
            <div class="mt-8 flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('server-modal-create').classList.add('hidden')" class="px-4 py-2 text-[10px] font-black uppercase tracking-widest text-slate-500 hover:text-white transition">
                    {{ __('Cancelar') }}
                </button>
                <button type="submit" class="bg-[#5b7cff] px-6 py-2 rounded-sm text-[10px] font-black uppercase tracking-widest text-white hover:bg-[#7c5cff] transition">
                    {{ __('Guardar') }}
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Server Modal -->
<div id="server-modal-edit" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 hidden">
    <div class="w-full max-w-md bg-[#1b1b1b] border border-[#222222] p-8 rounded-sm shadow-2xl">
        <h3 class="text-2xl font-black uppercase tracking-tighter text-white italic mb-6">{{ __('Editar Servidor') }}</h3>
        <form id="edit-server-form" action="" method="POST">
            @csrf
            @method('PUT')
            <div class="space-y-4">
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-2">{{ __('Nombre') }}</label>
                    <input type="text" name="name" id="edit-name" required class="w-full bg-[#121212] border border-[#222222] rounded-sm px-4 py-2 text-sm text-white focus:border-[#5b7cff] outline-none">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-2">{{ __('IP') }}</label>
                        <input type="text" name="ip" id="edit-ip" required class="w-full bg-[#121212] border border-[#222222] rounded-sm px-4 py-2 text-sm text-white focus:border-[#5b7cff] outline-none">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-2">{{ __('Puerto') }}</label>
                        <input type="number" name="port" id="edit-port" required class="w-full bg-[#121212] border border-[#222222] rounded-sm px-4 py-2 text-sm text-white focus:border-[#5b7cff] outline-none">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-2">{{ __('Tipo') }}</label>
                        <select name="type" id="edit-type" class="w-full bg-[#121212] border border-[#222222] rounded-sm px-4 py-2 text-sm text-white focus:border-[#5b7cff] outline-none">
                            <option value="mm">Matchmaking</option>
                            <option value="public">Público</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-2">{{ __('Estado') }}</label>
                        <select name="status" id="edit-status" class="w-full bg-[#121212] border border-[#222222] rounded-sm px-4 py-2 text-sm text-white focus:border-[#5b7cff] outline-none">
                            <option value="online">Online</option>
                            <option value="offline">Offline</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-2">{{ __('Máx. Jugadores') }}</label>
                        <input type="number" name="max_players" id="edit-max-players" required class="w-full bg-[#121212] border border-[#222222] rounded-sm px-4 py-2 text-sm text-white focus:border-[#5b7cff] outline-none">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-2">{{ __('RCON Password') }}</label>
                        <input type="password" name="rcon_password" id="edit-rcon" placeholder="Dejar en blanco para no cambiar" class="w-full bg-[#121212] border border-[#222222] rounded-sm px-4 py-2 text-sm text-white focus:border-[#5b7cff] outline-none">
                    </div>
                </div>
            </div>
            <div class="mt-8 flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('server-modal-edit').classList.add('hidden')" class="px-4 py-2 text-[10px] font-black uppercase tracking-widest text-slate-500 hover:text-white transition">
                    {{ __('Cancelar') }}
                </button>
                <button type="submit" class="bg-[#5b7cff] px-6 py-2 rounded-sm text-[10px] font-black uppercase tracking-widest text-white hover:bg-[#7c5cff] transition">
                    {{ __('Actualizar') }}
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function editServer(server) {
        const modal = document.getElementById('server-modal-edit');
        const form = document.getElementById('edit-server-form');
        
        form.action = `/admin/servers/${server.id}`;
        document.getElementById('edit-name').value = server.name;
        document.getElementById('edit-ip').value = server.ip;
        document.getElementById('edit-port').value = server.port;
        document.getElementById('edit-type').value = server.type;
        document.getElementById('edit-status').value = server.status;
        document.getElementById('edit-max-players').value = server.max_players;
        document.getElementById('edit-rcon').value = ''; 
        
        modal.classList.remove('hidden');
    }
</script>
@endsection
