@extends('layouts.app')

@section('title', 'Servidores')

@section('content')
<div class="app-root min-h-screen bg-[#121212] text-slate-100 p-8">
    <div class="max-w-6xl mx-auto">
        <div class="mb-6 flex items-center justify-between">
            <h3 class="text-2xl font-black uppercase tracking-tight text-white">Servidores Disponibles</h3>
            <span id="servers-status" class="text-xs uppercase tracking-[0.2em] text-slate-500">Cargando...</span>
        </div>

        <div id="servers-loading" class="rounded-sm border border-[#222222] bg-[#1b1b1b] p-6 text-sm text-slate-300">
            Cargando servidores...
        </div>

        <div id="servers-table" class="hidden">
            <!-- Header for desktop -->
            <div class="hidden md:grid grid-cols-12 gap-4 bg-[#222222] p-4 rounded-sm mb-2 text-[10px] uppercase font-bold tracking-widest text-slate-500 border border-[#333333]">
                <div class="col-span-5">Servidor</div>
                <div class="col-span-2">Estado</div>
                <div class="col-span-2">Jugadores</div>
                <div class="col-span-3 text-right">Acción</div>
            </div>
            
            <!-- Container for rows -->
            <div id="servers-body" class="space-y-3 md:space-y-1"></div>
        </div>
    </div>
</div>

<script>
(() => {
    const dataUrl = @json(route('servers.data'));
    const loadingEl = document.getElementById('servers-loading');
    const tableEl = document.getElementById('servers-table');
    const bodyEl = document.getElementById('servers-body');
    const statusEl = document.getElementById('servers-status');

    const escapeHtml = (value) => {
        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    };

    const renderRows = (servers) => {
        bodyEl.innerHTML = servers.map((server) => {
            const name = escapeHtml(server.name ?? 'Servidor');
            const status = String(server.runtime_status ?? 'offline');
            const statusClass = status === 'online'
                ? 'text-emerald-400'
                : 'text-slate-500';
            const isUnlimited = server.type === 'public';
            const ipLabel = `${escapeHtml(server.ip ?? '')}:${escapeHtml(String(server.port ?? ''))}`;
            
            const playersLabel = isUnlimited
                ? `<span class="flex items-center gap-2 text-emerald-400 font-bold"><span class="text-lg leading-none">∞</span><span class="text-[9px] uppercase tracking-[0.2em]">Pública</span></span>`
                : `<span class="font-bold text-white">${server.current_players ?? 0}</span><span class="text-slate-600 ml-1">/ ${server.max_players ?? 0}</span>`;
            
            const typeBadge = isUnlimited
                ? '<span class="px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider rounded-sm border border-emerald-500/30 text-emerald-400 bg-emerald-500/5">Pública</span>'
                : '<span class="px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider rounded-sm border border-[#ff5500]/30 text-[#ff5500] bg-[#ff5500]/5 text-center">Matchmaking</span>';
            
            const joinAction = status === 'online'
                ? `<a href="/lobby/${server.id}" class="w-full md:w-auto inline-block bg-[#ff5500] hover:bg-[#ff7733] text-white font-black py-2.5 px-6 rounded-sm transition uppercase text-[11px] tracking-widest text-center shadow-lg shadow-black/20">Unirse</a>`
                : `<span class="w-full md:w-auto inline-block bg-[#1b1b1b] text-slate-600 font-black py-2.5 px-6 rounded-sm border border-[#222222] cursor-not-allowed uppercase text-[11px] tracking-widest text-center">Offline</span>`;

            return `
                <div class="bg-[#1b1b1b] border border-[#222222] p-4 md:p-0 md:bg-transparent md:border-0 md:grid md:grid-cols-12 md:gap-4 md:items-center md:px-4 md:py-3 hover:bg-[#222222]/40 transition rounded-sm relative group">
                    <!-- Mobile Label for Name -->
                    <div class="col-span-5 flex flex-col gap-1 mb-4 md:mb-0">
                        <div class="flex items-center gap-3">
                            <span class="font-black text-white uppercase tracking-tight text-base md:text-sm italic">${name}</span>
                            ${typeBadge}
                        </div>
                        <p class="text-[10px] text-slate-500 font-bold font-mono">IP: ${ipLabel}</p>
                    </div>

                    <!-- Status -->
                    <div class="col-span-2 flex items-center justify-between md:justify-start mb-3 md:mb-0 border-t border-[#222222] pt-3 md:border-0 md:pt-0">
                        <span class="text-[10px] uppercase font-bold text-slate-600 md:hidden">Estado</span>
                        <div class="flex items-center gap-2">
                            <span class="h-1.5 w-1.5 rounded-full ${status === 'online' ? 'bg-emerald-500 animate-pulse' : 'bg-slate-700'}"></span>
                            <span class="text-[11px] font-black uppercase tracking-widest ${statusClass}">${status}</span>
                        </div>
                    </div>

                    <!-- Players -->
                    <div class="col-span-2 flex items-center justify-between md:justify-start mb-4 md:mb-0 border-t border-[#222222] pt-3 md:border-0 md:pt-0">
                        <span class="text-[10px] uppercase font-bold text-slate-600 md:hidden">Jugadores</span>
                        <div class="text-sm">${playersLabel}</div>
                    </div>

                    <!-- Action -->
                    <div class="col-span-3 text-right border-t border-[#222222] pt-4 md:border-0 md:pt-0">
                        ${joinAction}
                    </div>
                </div>
            `;
        }).join('');
    };

    const loadServers = async () => {
        try {
            const response = await fetch(dataUrl, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                cache: 'no-store',
            });

            if (!response.ok) {
                throw new Error('load-failed');
            }

            const data = await response.json();
            renderRows(data.servers || []);
            loadingEl.classList.add('hidden');
            tableEl.classList.remove('hidden');
            statusEl.textContent = 'Actualizado';
        } catch (error) {
            loadingEl.textContent = 'No se pudo cargar la lista. Reintenta.';
            statusEl.textContent = 'Error';
        }
    };

    loadServers();
})();
</script>
@endsection
