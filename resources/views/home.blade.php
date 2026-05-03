@extends('layouts.app')

@section('title', __('SERVIDORES'))

@section('content')
<div class="app-root min-h-screen bg-[#121212] text-slate-100 p-8">
    <div class="max-w-6xl mx-auto">
        <div class="mb-6 flex items-center justify-between">
            <h3 class="text-2xl font-black uppercase tracking-tight text-white">{{ __('Servidores Disponibles') }}</h3>
            <span id="servers-status" class="text-xs uppercase tracking-[0.2em] text-slate-500">{{ __('Actualizado') }}</span>
        </div>

        <div id="servers-table">
            <!-- Header for desktop -->
            <div class="hidden md:grid grid-cols-12 gap-4 bg-[#222222] p-4 rounded-sm mb-2 text-[10px] uppercase font-bold tracking-widest text-slate-500 border border-[#333333]">
                <div class="col-span-5">{{ __('Servidor') }}</div>
                <div class="col-span-2">{{ __('Estado') }}</div>
                <div class="col-span-2">{{ __('Jugadores') }}</div>
                <div class="col-span-3 text-right">{{ __('Acción') }}</div>
            </div>
            
            <!-- Container for rows -->
            <div id="servers-body" class="space-y-3 md:space-y-1">
                @forelse ($servers as $server)
                    @php
                        $status = (string) ($server['runtime_status'] ?? 'offline');
                        $isUnlimited = ($server['type'] ?? null) === 'public';
                    @endphp
                    <div class="bg-[#1b1b1b] border border-[#222222] p-4 md:p-0 md:bg-transparent md:border-0 md:grid md:grid-cols-12 md:gap-4 md:items-center md:px-4 md:py-3 hover:bg-[#222222]/40 transition rounded-sm relative group">
                        <div class="col-span-5 flex flex-col gap-1 mb-4 md:mb-0">
                            <div class="flex items-center gap-3">
                                <span class="font-black text-white uppercase tracking-tight text-base md:text-sm italic">{{ $server['name'] }}</span>
                                @if ($isUnlimited)
                                    <span class="px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider rounded-sm border border-emerald-500/30 text-emerald-400 bg-emerald-500/5">{{ __('Pública') }}</span>
                                @else
                                    <span class="px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider rounded-sm border border-[#5b7cff]/30 text-[#5b7cff] bg-[#5b7cff]/5 text-center">Matchmaking</span>
                                @endif
                            </div>
                            <p class="text-[10px] text-slate-500 font-bold uppercase tracking-[0.18em]">{{ __('Acceso por lobby') }}</p>
                        </div>

                        <div class="col-span-2 flex items-center justify-between md:justify-start mb-3 md:mb-0 border-t border-[#222222] pt-3 md:border-0 md:pt-0">
                            <span class="text-[10px] uppercase font-bold text-slate-600 md:hidden">{{ __('Estado') }}</span>
                            <div class="flex items-center gap-2">
                                <span class="h-1.5 w-1.5 rounded-full {{ $status === 'online' ? 'bg-emerald-500 animate-pulse' : 'bg-slate-700' }}"></span>
                                <span class="text-[11px] font-black uppercase tracking-widest {{ $status === 'online' ? 'text-emerald-400' : 'text-slate-500' }}">{{ $status }}</span>
                            </div>
                        </div>

                        <div class="col-span-2 flex items-center justify-between md:justify-start mb-4 md:mb-0 border-t border-[#222222] pt-3 md:border-0 md:pt-0">
                            <span class="text-[10px] uppercase font-bold text-slate-600 md:hidden">{{ __('Jugadores') }}</span>
                            <div class="text-sm">
                                <span class="font-bold text-white">{{ $server['current_players'] ?? 0 }}</span><span class="text-slate-600 ml-1">/ {{ $server['max_players'] ?? 0 }}</span>
                            </div>
                        </div>

                        <div class="col-span-3 text-right border-t border-[#222222] pt-4 md:border-0 md:pt-0">
                            @if ($status === 'online')
                                <a href="/lobby/{{ $server['id'] }}" class="w-full md:w-auto inline-block bg-[#5b7cff] hover:bg-[#7c5cff] text-white font-black py-2.5 px-6 rounded-sm transition uppercase text-[11px] tracking-widest text-center shadow-lg shadow-black/20">{{ __('Unirse') }}</a>
                            @else
                                <span class="w-full md:w-auto inline-block bg-[#1b1b1b] text-slate-600 font-black py-2.5 px-6 rounded-sm border border-[#222222] cursor-not-allowed uppercase text-[11px] tracking-widest text-center">Offline</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="rounded-sm border border-[#222222] bg-[#1b1b1b] p-6 text-sm text-slate-300">
                        {{ __('No hay servidores registrados todavía.') }}
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
<script>
(() => {
    const dataUrl = @json(route('servers.data'));
    const bodyEl = document.getElementById('servers-body');
    const statusEl = document.getElementById('servers-status');

    const escapeHtml = (value) => String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    const renderRows = (servers) => {
        bodyEl.innerHTML = servers.map((server) => {
            const name = escapeHtml(server.name ?? '{{ __('Servidor') }}');
            const status = String(server.runtime_status ?? 'offline');
            const isUnlimited = server.type === 'public';
            const players = Number(server.current_players ?? 0);
            const maxPlayers = Number(server.max_players ?? 0);
            const addressLine = `<p class="text-[10px] text-slate-500 font-bold uppercase tracking-[0.18em]">{{ __('Acceso por lobby') }}</p>`;

            return `
                <div class="bg-[#1b1b1b] border border-[#222222] p-4 md:p-0 md:bg-transparent md:border-0 md:grid md:grid-cols-12 md:gap-4 md:items-center md:px-4 md:py-3 hover:bg-[#222222]/40 transition rounded-sm relative group">
                    <div class="col-span-5 flex flex-col gap-1 mb-4 md:mb-0">
                        <div class="flex items-center gap-3">
                            <span class="font-black text-white uppercase tracking-tight text-base md:text-sm italic">${name}</span>
                            ${isUnlimited
                                ? '<span class="px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider rounded-sm border border-emerald-500/30 text-emerald-400 bg-emerald-500/5">{{ __('Pública') }}</span>'
                                : '<span class="px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider rounded-sm border border-[#5b7cff]/30 text-[#5b7cff] bg-[#5b7cff]/5 text-center">Matchmaking</span>'}
                        </div>
                        ${addressLine}
                    </div>

                    <div class="col-span-2 flex items-center justify-between md:justify-start mb-3 md:mb-0 border-t border-[#222222] pt-3 md:border-0 md:pt-0">
                        <span class="text-[10px] uppercase font-bold text-slate-600 md:hidden">{{ __('Estado') }}</span>
                        <div class="flex items-center gap-2">
                            <span class="h-1.5 w-1.5 rounded-full ${status === 'online' ? 'bg-emerald-500 animate-pulse' : 'bg-slate-700'}"></span>
                            <span class="text-[11px] font-black uppercase tracking-widest ${status === 'online' ? 'text-emerald-400' : 'text-slate-500'}">${escapeHtml(status)}</span>
                        </div>
                    </div>

                    <div class="col-span-2 flex items-center justify-between md:justify-start mb-4 md:mb-0 border-t border-[#222222] pt-3 md:border-0 md:pt-0">
                        <span class="text-[10px] uppercase font-bold text-slate-600 md:hidden">{{ __('Jugadores') }}</span>
                        <div class="text-sm">
                            <span class="font-bold text-white">${players}</span><span class="text-slate-600 ml-1">/ ${maxPlayers}</span>
                        </div>
                    </div>

                    <div class="col-span-3 text-right border-t border-[#222222] pt-4 md:border-0 md:pt-0">
                        ${status === 'online'
                            ? `<a href="/lobby/${server.id}" class="w-full md:w-auto inline-block bg-[#5b7cff] hover:bg-[#7c5cff] text-white font-black py-2.5 px-6 rounded-sm transition uppercase text-[11px] tracking-widest text-center shadow-lg shadow-black/20">{{ __('Unirse') }}</a>`
                            : `<span class="w-full md:w-auto inline-block bg-[#1b1b1b] text-slate-600 font-black py-2.5 px-6 rounded-sm border border-[#222222] cursor-not-allowed uppercase text-[11px] tracking-widest text-center">Offline</span>`}
                    </div>
                </div>
            `;
        }).join('');
    };

    const refreshServers = async () => {
        try {
            const response = await fetch(dataUrl, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                cache: 'no-store',
            });

            if (!response.ok) {
                throw new Error('refresh-failed');
            }

            const data = await response.json();
            renderRows(data.servers || []);
            statusEl.textContent = '{{ __('Actualizado') }}';
        } catch (error) {
            statusEl.textContent = '{{ __('Error') }}';
        }
    };

    refreshServers();
    window.setInterval(refreshServers, 5000);
})();
</script>
@endsection
