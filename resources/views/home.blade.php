@extends('layouts.app')

@section('title', 'Servidores')

@section('content')
<div class="app-root min-h-screen bg-slate-950 text-slate-100 p-8">
    <div class="max-w-6xl mx-auto">
        <div class="mb-6 flex items-center justify-between">
            <h3 class="text-2xl font-black uppercase tracking-tight text-slate-100">Servidores Disponibles</h3>
            <span id="servers-status" class="text-xs uppercase tracking-[0.2em] text-slate-500">Cargando...</span>
        </div>

        <div id="servers-loading" class="rounded-xl border border-slate-800 bg-slate-900/80 p-6 text-sm text-slate-300">
            Loading...
        </div>

        <div id="servers-table" class="hidden overflow-hidden rounded-xl border border-slate-800 bg-slate-900/80">
            <table class="w-full text-left">
                <thead class="bg-slate-800 text-slate-300 text-sm uppercase">
                    <tr>
                        <th class="p-4">Servidor</th>
                        <th class="p-4">Estado</th>
                        <th class="p-4">Jugadores</th>
                        <th class="p-4 text-right">Accion</th>
                    </tr>
                </thead>
                <tbody id="servers-body" class="divide-y divide-slate-800"></tbody>
            </table>
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
                ? 'bg-blue-500/20 text-blue-300'
                : 'bg-slate-700 text-slate-300';
            const players = `${server.current_players ?? 0} / ${server.max_players ?? 0}`;
            const joinAction = status === 'online'
                ? `<a href="/lobby/${server.id}" class="inline-block bg-blue-600 hover:bg-blue-500 text-white font-bold py-2 px-6 rounded-md transition">UNIRSE AL LOBBY</a>`
                : `<span class="inline-block bg-slate-800 text-slate-400 font-bold py-2 px-6 rounded-md border border-slate-700 cursor-not-allowed">OFFLINE</span>`;

            return `
                <tr class="transition hover:bg-slate-800/60">
                    <td class="p-4 font-semibold">${name}</td>
                    <td class="p-4">
                        <span class="px-2 py-1 rounded text-xs ${statusClass}">${status.toUpperCase()}</span>
                    </td>
                    <td class="p-4 text-slate-400">${players}</td>
                    <td class="p-4 text-right">${joinAction}</td>
                </tr>
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
