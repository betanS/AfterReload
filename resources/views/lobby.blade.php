@extends('layouts.app')

@section('title', 'Lobby')

@section('content')
@php
    $ctPlayers = $lobby->users->filter(fn ($player) => $player->pivot?->team === 'ct');
    $tPlayers = $lobby->users->filter(fn ($player) => $player->pivot?->team === 't');
@endphp
<div class="app-root min-h-screen bg-slate-950 text-slate-100 p-6 md:p-8">
    <div class="mx-auto max-w-6xl">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('servers.index') }}" class="inline-flex items-center rounded-md border border-slate-700 px-4 py-2 text-sm font-semibold text-slate-200 hover:border-slate-500">
                Volver a servidores
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

                <div class="mt-6 grid gap-4 md:grid-cols-2">
                    <div class="rounded-lg border border-blue-500/30 bg-slate-950/60 p-4">
                        <div class="flex items-center justify-between">
                            <h2 class="text-sm font-bold uppercase tracking-widest text-blue-300">CT</h2>
                            <span class="text-xs text-slate-400"><span id="ct-count">{{ $ctCount }}</span>/<span id="team-size">{{ $teamSize }}</span></span>
                        </div>
                        <button id="join-ct" class="mt-3 w-full rounded-md border border-blue-500/40 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-blue-200 hover:border-blue-400">
                            Unirse a CT
                        </button>
                        <div id="ct-list" class="mt-4 grid gap-3">
                            @foreach($ctPlayers as $player)
                                <div class="flex items-center gap-3 rounded-lg border border-slate-800 bg-slate-950/70 px-4 py-3">
                                    <img src="{{ $player->avatar }}" alt="Avatar {{ $player->steam_nickname ?? $player->name }}" class="h-10 w-10 rounded-full border border-blue-500/60">
                                    <div>
                                        <p class="font-semibold">{{ $player->steam_nickname ?? $player->name }}</p>
                                        <p class="text-xs text-slate-400">Rango: {{ $player->rank_points }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="rounded-lg border border-red-500/30 bg-slate-950/60 p-4">
                        <div class="flex items-center justify-between">
                            <h2 class="text-sm font-bold uppercase tracking-widest text-red-300">T</h2>
                            <span class="text-xs text-slate-400"><span id="t-count">{{ $tCount }}</span>/<span id="team-size-alt">{{ $teamSize }}</span></span>
                        </div>
                        <button id="join-t" class="mt-3 w-full rounded-md border border-red-500/40 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-red-200 hover:border-red-400">
                            Unirse a T
                        </button>
                        <div id="t-list" class="mt-4 grid gap-3">
                            @foreach($tPlayers as $player)
                                <div class="flex items-center gap-3 rounded-lg border border-slate-800 bg-slate-950/70 px-4 py-3">
                                    <img src="{{ $player->avatar }}" alt="Avatar {{ $player->steam_nickname ?? $player->name }}" class="h-10 w-10 rounded-full border border-red-500/60">
                                    <div>
                                        <p class="font-semibold">{{ $player->steam_nickname ?? $player->name }}</p>
                                        <p class="text-xs text-slate-400">Rango: {{ $player->rank_points }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
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
                        Actualizando lobby en tiempo real.
                    </p>
                </div>
            </aside>
        </div>
    </div>
</div>

<script src="https://js.pusher.com/8.4.0/pusher.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.15.3/dist/echo.iife.js"></script>
<script>
(() => {
    const statusUrl = @json(route('lobby.status', $server));
    const leaveUrl = @json(route('lobby.leave', $server));
    const teamUrl = @json(route('lobby.team', $server));
    const csrfToken = @json(csrf_token());
    const serverId = @json($server->id);
    const pusherKey = @json(config('broadcasting.connections.pusher.key'));
    const pusherCluster = @json(config('broadcasting.connections.pusher.options.cluster'));
    const pusherHost = @json(config('broadcasting.connections.pusher.options.host'));
    const pusherPort = @json(config('broadcasting.connections.pusher.options.port'));
    const pusherScheme = @json(config('broadcasting.connections.pusher.options.scheme'));

    const playersCount = document.getElementById('players-count');
    const requiredPlayers = document.getElementById('required-players');
    const missingPlayers = document.getElementById('missing-players');
    const lobbyStatus = document.getElementById('lobby-status');
    const readyPanel = document.getElementById('ready-panel');
    const waitingPanel = document.getElementById('waiting-panel');
    const serverAddress = document.getElementById('server-address');
    const connectCommand = document.getElementById('connect-command');
    const joinMatchLink = document.getElementById('join-match-link');
    const ctList = document.getElementById('ct-list');
    const tList = document.getElementById('t-list');
    const ctCount = document.getElementById('ct-count');
    const tCount = document.getElementById('t-count');
    const teamSize = document.getElementById('team-size');
    const teamSizeAlt = document.getElementById('team-size-alt');
    const joinCt = document.getElementById('join-ct');
    const joinT = document.getElementById('join-t');
    let hasLeft = false;

    const escapeHtml = (value) => {
        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    };

    const renderTeam = (container, users, borderColor) => {
        container.innerHTML = users.map((user) => {
            const name = escapeHtml(user.name ?? 'Steam User');
            const avatar = escapeHtml(user.avatar ?? 'https://placehold.co/40x40');
            const rank = escapeHtml(user.rank_points ?? 0);

            return `
                <div class="flex items-center gap-3 rounded-lg border border-slate-800 bg-slate-950/70 px-4 py-3">
                    <img src="${avatar}" alt="Avatar ${name}" class="h-10 w-10 rounded-full border ${borderColor}">
                    <div>
                        <p class="font-semibold">${name}</p>
                        <p class="text-xs text-slate-400">Rango: ${rank}</p>
                    </div>
                </div>
            `;
        }).join('');
    };

    const applyPayload = (data) => {
        playersCount.textContent = data.lobby.users_count;
        requiredPlayers.textContent = data.lobby.required_players;
        missingPlayers.textContent = data.lobby.missing_players;
        lobbyStatus.textContent = String(data.lobby.status).toUpperCase();

        const address = `${data.server.ip}:${data.server.port}`;
        serverAddress.textContent = address;
        connectCommand.textContent = `connect ${address}`;
        joinMatchLink.setAttribute('href', `steam://connect/${address}`);

        const users = Array.isArray(data.users) ? data.users : [];
        const ctUsers = users.filter((user) => user.team === 'ct');
        const tUsers = users.filter((user) => user.team === 't');

        renderTeam(ctList, ctUsers, 'border-blue-500/60');
        renderTeam(tList, tUsers, 'border-red-500/60');

        ctCount.textContent = data.lobby.ct_count ?? ctUsers.length;
        tCount.textContent = data.lobby.t_count ?? tUsers.length;
        teamSize.textContent = data.lobby.team_size ?? 5;
        teamSizeAlt.textContent = data.lobby.team_size ?? 5;

        const currentTeam = data.lobby.current_team;
        const maxTeamSize = data.lobby.team_size ?? 5;
        joinCt.disabled = (data.lobby.ct_count ?? 0) >= maxTeamSize;
        joinT.disabled = (data.lobby.t_count ?? 0) >= maxTeamSize;

        if (currentTeam === 'ct') {
            joinCt.textContent = 'En CT';
            joinT.textContent = 'Unirse a T';
        } else if (currentTeam === 't') {
            joinT.textContent = 'En T';
            joinCt.textContent = 'Unirse a CT';
        } else {
            joinCt.textContent = 'Unirse a CT';
            joinT.textContent = 'Unirse a T';
        }

        if (data.is_ready) {
            readyPanel.classList.remove('hidden');
            waitingPanel.classList.add('hidden');
        } else {
            readyPanel.classList.add('hidden');
            waitingPanel.classList.remove('hidden');
        }
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
            applyPayload(data);
        } catch (error) {
            // Ignorar errores transitorios de red durante el polling.
        }
    };

    const initEcho = () => {
        if (!pusherKey || typeof Pusher === 'undefined' || typeof Echo === 'undefined') {
            return false;
        }

        const options = {
            broadcaster: 'pusher',
            key: pusherKey,
            cluster: pusherCluster || undefined,
            forceTLS: pusherScheme === 'https',
            wsHost: pusherHost || undefined,
            wsPort: pusherPort || undefined,
            wssPort: pusherPort || undefined,
            enabledTransports: ['ws', 'wss'],
        };

        window.Echo = new Echo(options);
        window.Echo.channel(`lobby.${serverId}`)
            .listen('.LobbyUpdated', (data) => applyPayload(data));

        return true;
    };

    const sendLeave = () => {
        if (hasLeft) {
            return;
        }

        hasLeft = true;

        const formData = new FormData();
        formData.append('_token', csrfToken);

        const beaconSent = navigator.sendBeacon(leaveUrl, formData);

        if (!beaconSent) {
            fetch(leaveUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: new URLSearchParams({ _token: csrfToken }),
                keepalive: true,
            }).catch(() => {});
        }
    };

    const setTeam = async (team) => {
        try {
            const response = await fetch(teamUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: new URLSearchParams({ team }),
            });

            if (!response.ok) {
                return;
            }

            const data = await response.json();
            applyPayload(data);
        } catch (error) {
            // ignore
        }
    };

    joinCt.addEventListener('click', () => setTeam('ct'));
    joinT.addEventListener('click', () => setTeam('t'));

    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            sendLeave();
        }
    });

    window.addEventListener('beforeunload', sendLeave);
    window.addEventListener('pagehide', sendLeave);

    const echoReady = initEcho();

    if (!echoReady) {
        setInterval(updateLobby, 1000);
    }
})();
</script>
@endsection
