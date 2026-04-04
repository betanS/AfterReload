@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
<div class="p-4 md:p-8 max-w-7xl mx-auto">
    <div class="mb-8">
        <h2 class="text-3xl font-black uppercase italic tracking-tighter text-white">Admin Dashboard</h2>
        <p class="text-xs font-bold uppercase tracking-widest text-[#ff5500] mt-1">Gestión centralizada de la plataforma</p>
    </div>

    @if(session('status'))
        <div class="mb-6 rounded-sm border border-[#ff5500]/30 bg-[#ff5500]/10 px-4 py-3 text-sm text-white font-bold">
            {{ session('status') }}
        </div>
    @endif

    <!-- Stats Grid -->
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-8">
        <div class="bg-[#1b1b1b] border border-[#222222] p-6 rounded-sm">
            <p class="text-[10px] uppercase font-bold text-slate-500 tracking-widest mb-1">Total Usuarios</p>
            <p class="text-3xl font-black text-white italic">{{ $users->count() }}</p>
        </div>
        <div class="bg-[#1b1b1b] border border-[#222222] p-6 rounded-sm">
            <p class="text-[10px] uppercase font-bold text-slate-500 tracking-widest mb-1">Admins</p>
            <p class="text-3xl font-black text-[#ff5500] italic">{{ $users->where('role', 'admin')->count() }}</p>
        </div>
        <div class="bg-[#1b1b1b] border border-[#222222] p-6 rounded-sm">
            <p class="text-[10px] uppercase font-bold text-slate-500 tracking-widest mb-1">Baneados</p>
            <p class="text-3xl font-black text-red-500 italic">{{ $users->whereNotNull('banned_at')->count() }}</p>
        </div>
        <div class="bg-[#1b1b1b] border border-[#222222] p-6 rounded-sm">
            <p class="text-[10px] uppercase font-bold text-slate-500 tracking-widest mb-1">Media Elo</p>
            <p class="text-3xl font-black text-white italic">{{ round($users->avg('rank_points') ?? 0) }}</p>
        </div>
    </div>

    <div class="bg-[#1b1b1b] border border-[#222222] rounded-sm shadow-xl overflow-hidden">
        <div class="p-6 border-b border-[#222222] flex flex-col md:flex-row md:items-center justify-between gap-4">
            <h3 class="text-sm font-black uppercase tracking-widest text-white">Lista de Usuarios</h3>
        </div>

        <div class="overflow-x-auto">
            <!-- Desktop Table -->
            <table class="hidden md:table w-full text-left text-sm">
                <thead class="bg-[#222222] text-slate-500 uppercase font-black tracking-widest text-[10px]">
                    <tr>
                        <th class="p-4">Usuario</th>
                        <th class="p-4">Rol</th>
                        <th class="p-4">Estado</th>
                        <th class="p-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#222222]">
                    @foreach($users as $user)
                        <tr class="hover:bg-[#222222]/30 transition group">
                            <td class="p-4">
                                <div class="flex items-center gap-3">
                                    <img src="{{ $user->avatar }}" class="h-10 w-10 rounded-sm border border-[#222222] group-hover:border-[#ff5500]/40 transition" alt="Avatar">
                                    <div>
                                        <p class="font-bold text-white uppercase tracking-tight">{{ $user->steam_nickname ?? $user->name }}</p>
                                        <p class="text-[10px] text-slate-500 font-mono">{{ $user->steam_id }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="p-4">
                                <form method="POST" action="{{ route('admin.users.role', $user) }}" class="flex items-center gap-2">
                                    @csrf
                                    <select name="role" class="bg-[#121212] border border-[#222222] rounded-sm px-2 py-1 text-[10px] font-black uppercase tracking-widest text-white focus:border-[#ff5500] outline-none">
                                        @foreach([
                                            'user' => 'User',
                                            'store' => 'Store',
                                            'admin' => 'Admin',
                                            'betatester' => 'Beta Tester',
                                        ] as $value => $label)
                                            <option value="{{ $value }}" @selected($user->role === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <button class="bg-[#ff5500] px-3 py-1 text-[10px] font-black text-white rounded-sm hover:bg-[#ff7733] transition uppercase tracking-widest">OK</button>
                                </form>
                            </td>
                            <td class="p-4">
                                <span class="px-2 py-1 rounded-sm text-[10px] font-black uppercase tracking-widest {{ $user->banned_at ? 'bg-red-500/10 text-red-500' : 'bg-emerald-500/10 text-emerald-500' }}">
                                    {{ $user->banned_at ? 'Baneado' : 'Activo' }}
                                </span>
                            </td>
                            <td class="p-4 text-right">
                                @if($user->banned_at)
                                    <form method="POST" action="{{ route('admin.users.unban', $user) }}" class="inline">
                                        @csrf
                                        <button class="px-3 py-1.5 bg-emerald-600 rounded-sm text-[10px] font-black uppercase tracking-widest text-white hover:bg-emerald-500 transition">
                                            UNBAN
                                        </button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.users.ban', $user) }}" class="inline">
                                        @csrf
                                        <button class="px-3 py-1.5 border border-red-500/30 bg-red-500/5 rounded-sm text-[10px] font-black uppercase tracking-widest text-red-500 hover:bg-red-500/10 transition">
                                            BAN
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- Mobile List (Cards) -->
            <div class="md:hidden divide-y divide-[#222222]">
                @foreach($users as $user)
                    <div class="p-4 space-y-4">
                        <div class="flex items-center gap-4">
                            <img src="{{ $user->avatar }}" class="h-12 w-12 rounded-sm border-2 border-[#ff5500]/40" alt="Avatar">
                            <div class="flex-1 min-w-0">
                                <p class="font-black text-white uppercase tracking-tight truncate">{{ $user->steam_nickname ?? $user->name }}</p>
                                <p class="text-[10px] text-slate-500 font-mono">{{ $user->steam_id }}</p>
                            </div>
                        </div>
                        
                        <div class="space-y-3 bg-[#121212] p-3 rounded-sm border border-[#222222]">
                            <form method="POST" action="{{ route('admin.users.role', $user) }}" class="flex flex-col gap-2">
                                @csrf
                                <label class="text-[9px] font-black uppercase text-slate-500 tracking-widest">Cambiar Rol</label>
                                <div class="flex gap-2">
                                    <select name="role" class="flex-1 bg-[#1b1b1b] border border-[#222222] rounded-sm px-2 py-2 text-[10px] font-black uppercase tracking-widest text-white">
                                        @foreach(['user' => 'User', 'store' => 'Store', 'admin' => 'Admin', 'betatester' => 'Beta Tester'] as $v => $l)
                                            <option value="{{ $v }}" @selected($user->role === $v)>{{ $l }}</option>
                                        @endforeach
                                    </select>
                                    <button class="bg-[#ff5500] px-4 py-2 text-[10px] font-black text-white rounded-sm uppercase">OK</button>
                                </div>
                            </form>
                        </div>

                        <div class="flex items-center justify-between gap-4">
                            <span class="px-2 py-1 rounded-sm text-[10px] font-black uppercase tracking-widest {{ $user->banned_at ? 'bg-red-500/10 text-red-500' : 'bg-emerald-500/10 text-emerald-500' }}">
                                {{ $user->banned_at ? 'Baneado' : 'Activo' }}
                            </span>

                            @if($user->banned_at)
                                <form method="POST" action="{{ route('admin.users.unban', $user) }}">
                                    @csrf
                                    <button class="px-4 py-2 bg-emerald-600 rounded-sm text-[10px] font-black uppercase tracking-widest text-white">
                                        Unban
                                    </button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('admin.users.ban', $user) }}">
                                    @csrf
                                    <button class="px-4 py-2 border border-red-500/30 bg-red-500/5 rounded-sm text-[10px] font-black uppercase tracking-widest text-red-500">
                                        Ban
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
