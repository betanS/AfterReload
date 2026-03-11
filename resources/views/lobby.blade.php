@extends('layouts.app')

@section('title', 'Lobby')

@section('content')
<div class="app-root min-h-screen bg-slate-950 text-slate-100 p-6 md:p-8">
    <div class="mx-auto max-w-6xl">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('home') }}" class="inline-flex items-center rounded-md border border-slate-700 px-4 py-2 text-sm font-semibold text-slate-200 hover:border-slate-500">
                Volver al Home
            </a>
            <span class="rounded-md bg-slate-900 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-blue-300 border border-slate-800">
                Lobby #<span id="lobby-id">{{ $lobby->id }}</span>
            </span>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <section class="lg:col-span-2 rounded-xl border border-slate-800 bg-slate-900/80 p-6">
                <h1 class="text-2xl font-black">{{ $server->name }}</h1>
                <p class="mt-2 text-sm text-slate-300">
                    Jugadores en lobby:
                    <span class="font-bold text-white" id="players-count">{{ $lobby->users_count }}</span>
                    /
                    <span id="required-players">{{ $lobby->required_players }}</span>
                </p>

                <div id="players-grid" class="mt-6 grid gap-3 sm:grid-cols-2">
                    @foreach($lobby->users as $player)
                        <div class="flex items-center gap-3 rounded-lg border border-slate-800 bg-slate-950/70 px-4 py-3">
                            <img src="{{ $player->avatar }}" alt="Avatar {{ $player->name }}" class="h-10 w-10 rounded-full border border-blue-500/60">
                            <div>
                                <p class="font-semibold">{{ $player->name }}</p>
                                <p class="text-xs text-slate-400">Steam: {{ $player->steam_id }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <aside class="rounded-xl border border-slate-800 bg-slate-900/80 p-6">
                <div id="ready-panel" class="{{ $isReady ? '' : 'hidden' }}">
                    <p class="text-xs font-bold uppercase tracking-widest text-blue-400">Match listo</p>
                    <h2 class="mt-2 text-xl font-black">Servidor desbloqueado</h2>
                    <p class="mt-3 text-sm text-slate-300">Ya puedes conectarte al servidor de CS:GO.</p>

                    <div class="mt-5 rounded-lg border border-blue-500/30 bg-blue-500/10 p-3">
                        <p class="text-xs text-blue-200">IP del servidor</p>
                        <p id="server-address" class="font-mono text-sm text-white">{{ $server->ip }}:{{ $server->port }}</p>
                        <p id="connect-command" class="mt-2 font-mono text-xs text-blue-200">connect {{ $server->ip }}:{{ $server->port }}</p>
                        <p id="connect-command-short" class="mt-1 font-mono text-xs text-blue-200">connect {{ $server->ip }}</p>
                    </div>

                    <a id="join-match-link" href="steam://connect/{{ $server->ip }}:{{ $server->port }}" class="mt-4 inline-block w-full rounded-md bg-blue-600 px-4 py-3 text-center text-sm font-bold uppercase tracking-wide text-white hover:bg-blue-500">
                        Join Match
                    </a>
                </div>

                <div id="waiting-panel" class="{{ $isReady ? 'hidden' : '' }}">
                    <p class="text-xs font-bold uppercase tracking-widest text-blue-400">Esperando jugadores</p>
                    <h2 class="mt-2 text-xl font-black">Lobby en cola</h2>
                    <p class="mt-3 text-sm text-slate-300">
                        Faltan <span id="missing-players" class="font-bold text-white">{{ $missingPlayers }}</span> jugador(es) para revelar el servidor.
                    </p>

                    <div class="mt-5 rounded-lg border border-slate-700 bg-slate-950/70 p-3">
                        <p class="text-xs text-slate-400">Estado actual</p>
                        <p id="lobby-status" class="text-sm font-semibold text-white">{{ strtoupper($lobby->status) }}</p>
                    </div>

                    <p class="mt-4 text-xs text-slate-500">
                        Actualizando lobby automaticamente cada 5 segundos.
                    </p>
                </div>
            </aside>
        </div>
    </div>
</div>

<script>
(() => {
    const statusUrl = @json(route('lobby.status', $server));
    const playersCount = document.getElementById('players-count');
    const requiredPlayers = document.getElementById('required-players');
    const missingPlayers = document.getElementById('missing-players');
    const lobbyStatus = document.getElementById('lobby-status');
    const readyPanel = document.getElementById('ready-panel');
    const waitingPanel = document.getElementById('waiting-panel');
    const serverAddress = document.getElementById('server-address');
    const connectCommand = document.getElementById('connect-command');
    const connectCommandShort = document.getElementById('connect-command-short');
    const joinMatchLink = document.getElementById('join-match-link');
    const playersGrid = document.getElementById('players-grid');

    const escapeHtml = (value) => {
        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    };

    const renderPlayers = (users) => {
        playersGrid.innerHTML = users.map((user) => {
            const name = escapeHtml(user.name ?? 'Steam User');
            const avatar = escapeHtml(user.avatar ?? 'https://placehold.co/40x40');
            const steamId = escapeHtml(user.steam_id ?? 'N/A');

            return `
                <div class="flex items-center gap-3 rounded-lg border border-slate-800 bg-slate-950/70 px-4 py-3">
                    <img src="${avatar}" alt="Avatar ${name}" class="h-10 w-10 rounded-full border border-blue-500/60">
                    <div>
                        <p class="font-semibold">${name}</p>
                        <p class="text-xs text-slate-400">Steam: ${steamId}</p>
                    </div>
                </div>
            `;
        }).join('');
    };

    const updateLobby = async () => {
        try {
            const response = await fetch(statusUrl, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                cache: 'no-store',
            });

            if (!response.ok) {
                return;
            }

            const data = await response.json();

            playersCount.textContent = data.lobby.users_count;
            requiredPlayers.textContent = data.lobby.required_players;
            missingPlayers.textContent = data.lobby.missing_players;
            lobbyStatus.textContent = String(data.lobby.status).toUpperCase();

            const address = `${data.server.ip}:${data.server.port}`;
            serverAddress.textContent = address;
            connectCommand.textContent = `connect ${address}`;
            connectCommandShort.textContent = `connect ${data.server.ip}`;
            joinMatchLink.setAttribute('href', `steam://connect/${address}`);

            renderPlayers(data.users);

            if (data.is_ready) {
                readyPanel.classList.remove('hidden');
                waitingPanel.classList.add('hidden');
            } else {
                readyPanel.classList.add('hidden');
                waitingPanel.classList.remove('hidden');
            }
        } catch (error) {
            // Ignorar errores transitorios de red durante el polling.
        }
    };

    setInterval(updateLobby, 5000);
})();
</script>
@endsection