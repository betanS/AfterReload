@extends('layouts.app')

@section('title', 'Lobby')

@section('content')
@php
    $ctPlayers = $lobby->users->filter(fn ($player) => $player->pivot?->team === 'ct');
    $tPlayers = $lobby->users->filter(fn ($player) => $player->pivot?->team === 't');
    $genericPlayers = $lobby->users;
@endphp
<div class="app-root min-h-screen bg-[#121212] text-slate-100 p-4 md:p-8">
    <div class="mx-auto max-w-6xl">
        <div class="mb-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <a href="{{ route('servers.index') }}" class="inline-flex items-center rounded-sm border border-[#222222] bg-[#1b1b1b] px-4 py-2 text-sm font-semibold text-slate-200 hover:border-[#333333] transition uppercase tracking-wider">
                Volver a servidores
            </a>
            <span class="rounded-sm bg-[#1b1b1b] px-3 py-2 text-xs font-bold uppercase tracking-widest text-[#ff5500] border border-[#222222]">
                Lobby #<span id="lobby-id">{{ $displayLobbyId }}</span>
            </span>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <section class="lg:col-span-2 rounded-sm border border-[#222222] bg-[#1b1b1b] p-4 md:p-6 shadow-xl">
                <h1 class="text-xl md:text-2xl font-black uppercase text-white tracking-tight">{{ $server->name }}</h1>
                <p class="mt-2 text-sm text-slate-400 uppercase tracking-widest font-semibold">
                    Jugadores en lobby:
                    <span class="font-bold text-[#ff5500]" id="players-count">{{ $lobby->users_count }}</span>
                    /
                    <span id="required-players">{{ $isUnlimitedLobby ? '∞' : $lobby->required_players }}</span>
                </p>

                @if(! $isUnlimitedLobby)
                    <div class="mt-6 grid gap-4 md:grid-cols-2">
                        <div class="rounded-sm border border-blue-500/20 bg-[#121212] p-3 md:p-4">
                            <div class="flex items-center justify-between">
                                <h2 class="text-xs md:text-sm font-bold uppercase tracking-widest text-blue-400">Counter-Terrorists</h2>
                                <span class="text-xs text-slate-500 font-bold"><span id="ct-count">{{ $ctCount }}</span>/<span id="team-size">{{ $teamSize }}</span></span>
                            </div>
                            <button id="join-ct" class="mt-3 w-full rounded-sm border border-blue-500/30 bg-blue-500/5 px-3 py-2 text-[10px] md:text-xs font-bold uppercase tracking-widest text-blue-300 hover:bg-blue-500/10 transition">
                                Unirse a CT
                            </button>
                            <div id="ct-list" class="mt-4 grid gap-2 md:gap-3">
                                @foreach($ctPlayers as $player)
                                    <div class="flex items-center gap-3 rounded-sm border border-[#222222] bg-[#1b1b1b] px-3 py-2 md:px-4 md:py-3 relative">
                                        <img src="{{ $player->avatar }}" alt="Avatar {{ $player->steam_nickname ?? $player->name }}" class="h-8 w-8 md:h-10 md:w-10 rounded-sm border border-blue-500/40">
                                        <div class="min-w-0 flex-1">
                                            <p class="font-bold text-sm truncate text-white">{{ $player->steam_nickname ?? $player->name }}</p>
                                            <p class="text-[10px] md:text-xs text-slate-500 font-semibold uppercase tracking-tighter">Elo: {{ $player->rank_points }}</p>
                                        </div>
                                        @if($player->pivot?->is_ready)
                                            <span class="absolute top-2 right-2 text-emerald-500" title="Listo">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                                            </span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="rounded-sm border border-red-500/20 bg-[#121212] p-3 md:p-4">
                            <div class="flex items-center justify-between">
                                <h2 class="text-xs md:text-sm font-bold uppercase tracking-widest text-red-400">Terrorists</h2>
                                <span class="text-xs text-slate-500 font-bold"><span id="t-count">{{ $tCount }}</span>/<span id="team-size-alt">{{ $teamSize }}</span></span>
                            </div>
                            <button id="join-t" class="mt-3 w-full rounded-sm border border-red-500/30 bg-red-500/5 px-3 py-2 text-[10px] md:text-xs font-bold uppercase tracking-widest text-red-300 hover:bg-red-500/10 transition">
                                Unirse a T
                            </button>
                            <div id="t-list" class="mt-4 grid gap-2 md:gap-3">
                                @foreach($tPlayers as $player)
                                    <div class="flex items-center gap-3 rounded-sm border border-[#222222] bg-[#1b1b1b] px-3 py-2 md:px-4 md:py-3 relative">
                                        <img src="{{ $player->avatar }}" alt="Avatar {{ $player->steam_nickname ?? $player->name }}" class="h-8 w-8 md:h-10 md:w-10 rounded-sm border border-red-500/40">
                                        <div class="min-w-0 flex-1">
                                            <p class="font-bold text-sm truncate text-white">{{ $player->steam_nickname ?? $player->name }}</p>
                                            <p class="text-[10px] md:text-xs text-slate-500 font-semibold uppercase tracking-tighter">Elo: {{ $player->rank_points }}</p>
                                        </div>
                                        @if($player->pivot?->is_ready)
                                            <span class="absolute top-2 right-2 text-emerald-500" title="Listo">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                                            </span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @else
                    <div class="mt-6 rounded-sm border border-[#222222] bg-[#121212] p-4">
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-500 font-bold mb-4">Jugadores conectados</p>
                        <div id="generic-list" class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-3">
                            @foreach($genericPlayers as $player)
                                <div class="flex items-center gap-3 rounded-sm border border-[#222222] bg-[#1b1b1b] px-3 py-2">
                                    <img src="{{ $player->avatar }}" alt="Avatar {{ $player->steam_nickname ?? $player->name }}" class="h-8 w-8 rounded-sm border border-[#ff5500]/40">
                                    <div class="min-w-0 flex-1">
                                        <p class="font-bold text-sm truncate text-white">{{ $player->steam_nickname ?? $player->name }}</p>
                                        <p class="text-[10px] text-slate-500 font-semibold uppercase tracking-tighter">Elo: {{ $player->rank_points }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </section>

            <aside class="rounded-sm border border-[#222222] bg-[#1b1b1b] p-4 md:p-6 shadow-xl h-fit">
                @if($isUnlimitedLobby)
                    <div class="mb-4 rounded-sm border border-[#ff5500]/20 bg-[#ff5500]/5 p-4">
                        <p class="text-[10px] text-[#ff5500] uppercase tracking-widest font-bold mb-1">IP pública del servidor</p>
                        <p id="public-server-address" class="font-mono text-sm text-white break-all bg-[#121212] p-2 rounded-sm border border-[#222222]">{{ $server->ip }}:{{ $server->port }}</p>
                        <p id="public-connect-command" class="mt-2 font-mono text-[10px] md:text-xs text-slate-500 break-all">connect {{ $server->ip }}:{{ $server->port }}</p>
                        <a id="public-join-link" href="steam://connect/{{ $server->ip }}:{{ $server->port }}" class="mt-4 inline-block w-full rounded-sm bg-[#ff5500] px-4 py-3 text-center text-xs font-bold uppercase tracking-widest text-white hover:bg-[#ff7733] transition shadow-lg shadow-black/40">
                            Conectar ahora
                        </a>
                    </div>
                @endif
                
                <div id="ready-panel" class="{{ ($isReady && !$isUnlimitedLobby) ? '' : 'hidden' }}">
                    <p class="text-[10px] md:text-xs font-bold uppercase tracking-widest text-emerald-400 mb-1">Partida lista</p>
                    <h2 class="text-lg md:text-xl font-black uppercase text-white tracking-tight">Servidor desbloqueado</h2>
                    <p class="mt-3 text-sm text-slate-400">El match ha sido configurado. Puedes conectarte ahora.</p>

                    <div class="mt-5 rounded-sm border border-[#222222] bg-[#121212] p-4">
                        @unless($isUnlimitedLobby)
                            <p class="text-[10px] text-slate-500 uppercase tracking-widest font-bold mb-1">IP del servidor</p>
                            <p id="server-address" class="font-mono text-sm text-white break-all">{{ $server->ip }}:{{ $server->port }}</p>
                            <p id="connect-command" class="mt-2 font-mono text-[10px] md:text-xs text-[#ff5500] break-all">connect {{ $server->ip }}:{{ $server->port }}</p>
                        @endunless
                    </div>

                    <a id="join-match-link" href="steam://connect/{{ $server->ip }}:{{ $server->port }}" class="mt-6 inline-block w-full rounded-sm bg-[#ff5500] px-4 py-4 text-center text-sm font-bold uppercase tracking-widest text-white hover:bg-[#ff7733] transition shadow-lg shadow-black/40">
                        CONECTAR AL MATCH
                    </a>
                </div>

                <div id="waiting-panel" class="{{ ($isReady && !$isUnlimitedLobby) ? 'hidden' : '' }}">
                    @if($isUnlimitedLobby)
                        <p class="text-[10px] md:text-xs font-bold uppercase tracking-widest text-[#ff5500] mb-1">Estado del servidor</p>
                        <h2 class="text-lg md:text-xl font-black uppercase text-white tracking-tight">Servidor Activo</h2>
                    @else
                        <p class="text-[10px] md:text-xs font-bold uppercase tracking-widest text-[#ff5500] mb-1">Esperando jugadores</p>
                        <h2 class="text-lg md:text-xl font-black uppercase text-white tracking-tight">En cola para match</h2>
                    @endif
                    
                    @if($isUnlimitedLobby)
                        <p class="mt-3 text-sm text-slate-400">
                            Servidor público abierto. Conéctate para jugar.
                        </p>
                    @else
                        <div id="ready-section" class="mt-6">
                            <button id="toggle-ready" class="w-full rounded-sm bg-emerald-600 px-4 py-4 text-center text-sm font-bold uppercase tracking-widest text-white hover:bg-emerald-500 transition disabled:opacity-50 shadow-lg shadow-black/40">
                                ESTOY LISTO
                            </button>
                            <p class="mt-3 text-[10px] text-center text-slate-500 uppercase tracking-widest font-bold">Confirma tu asistencia</p>
                        </div>

                        <p class="mt-8 text-sm text-slate-400 font-semibold">
                            Faltan <span id="missing-players" class="font-bold text-[#ff5500]">{{ $missingPlayers }}</span> jugadores para iniciar.
                        </p>
                    @endif

                    <div class="mt-6 rounded-sm border border-[#222222] bg-[#121212] p-4">
                        <p class="text-[10px] text-slate-500 uppercase tracking-widest font-bold mb-1">Estado</p>
                        <p id="lobby-status" class="text-sm font-bold text-white uppercase tracking-wider">{{ strtoupper($lobby->status) }}</p>
                    </div>

                    <p class="mt-6 text-[10px] text-slate-600 uppercase tracking-widest font-bold flex items-center gap-2">
                        <span class="flex h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        Live Update
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
    const heartbeatUrl = @json(route('lobby.heartbeat', $server));
    const leaveUrl = @json(route('lobby.leave', $server));
    const teamUrl = @json(route('lobby.team', $server));
    const readyUrl = @json(route('lobby.ready', $server));
    const csrfToken = @json(csrf_token());
    const serverId = @json($server->id);
    const isUnlimitedLobby = @json($isUnlimitedLobby);
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
    const genericList = document.getElementById('generic-list');
    const ctCount = document.getElementById('ct-count');
    const tCount = document.getElementById('t-count');
    const teamSize = document.getElementById('team-size');
    const teamSizeAlt = document.getElementById('team-size-alt');
    const joinCt = document.getElementById('join-ct');
    const joinT = document.getElementById('join-t');
    const toggleReadyBtn = document.getElementById('toggle-ready');
    const publicServerAddress = document.getElementById('public-server-address');
    const publicConnectCommand = document.getElementById('public-connect-command');
    const publicJoinLink = document.getElementById('public-join-link');
    let hasLeft = false;
    let lobbyLocked = !isUnlimitedLobby && @json($lobby->status === 'live' && $lobby->started_at !== null);

    const escapeHtml = (value) => {
        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    };

    const renderTeam = (container, users, borderColor) => {
        if (!container) return;
        container.innerHTML = users.map((user) => {
            const name = escapeHtml(user.name ?? 'Steam User');
            const avatar = escapeHtml(user.avatar ?? 'https://placehold.co/40x40');
            const rank = escapeHtml(user.rank_points ?? 0);
            const isReady = !!user.is_ready;

            return `
                <div class="flex items-center gap-3 rounded-sm border border-[#222222] bg-[#1b1b1b] px-4 py-3 relative">
                    <img src="${avatar}" alt="Avatar ${name}" class="h-10 w-10 rounded-sm border ${borderColor}">
                    <div class="min-w-0 flex-1">
                        <p class="font-bold text-white truncate text-sm">${name}</p>
                        <p class="text-[10px] text-slate-500 uppercase font-bold tracking-tighter">Elo: ${rank}</p>
                    </div>
                    ${isReady ? '<span class="absolute top-2 right-2 text-emerald-500"><svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg></span>' : ''}
                </div>
            `;
        }).join('');
    };

    const applyPayload = (data) => {
        playersCount.textContent = data.lobby.users_count;
        requiredPlayers.textContent = data.lobby.is_unlimited ? '∞' : data.lobby.required_players;
        if (missingPlayers) {
            missingPlayers.textContent = data.lobby.missing_players;
        }
        lobbyStatus.textContent = String(data.lobby.status).toUpperCase();

        const address = `${data.server.ip}:${data.server.port}`;
        const password = data.server.password;
        if (serverAddress) {
            serverAddress.textContent = address;
        }
        
        let connectCmd = `connect ${address}`;
        let steamConnect = `steam://connect/${address}`;
        
        if (password) {
            connectCmd += `; password ${password}`;
            steamConnect += `/${password}`;
        }
        
        if (connectCommand) {
            connectCommand.textContent = connectCmd;
        }
        if (joinMatchLink) {
            joinMatchLink.setAttribute('href', steamConnect);
        }

        if (publicServerAddress) {
            publicServerAddress.textContent = address;
        }
        if (publicConnectCommand) {
            publicConnectCommand.textContent = connectCmd;
        }
        if (publicJoinLink) {
            publicJoinLink.setAttribute('href', steamConnect);
        }

        const users = Array.isArray(data.users) ? data.users : [];
        const isUnlimited = !!data.lobby.is_unlimited;
        
        if (isUnlimited && genericList) {
            renderTeam(genericList, users, 'border-blue-500/60');
        } else {
            const ctUsers = users.filter((user) => user.team === 'ct');
            const tUsers = users.filter((user) => user.team === 't');

            if (ctList) {
                renderTeam(ctList, ctUsers, 'border-blue-500/60');
            }
            if (tList) {
                renderTeam(tList, tUsers, 'border-red-500/60');
            }
        }

        if (ctCount) {
            ctCount.textContent = data.lobby.ct_count ?? 0;
        }
        if (tCount) {
            tCount.textContent = data.lobby.t_count ?? 0;
        }
        if (teamSize) {
            teamSize.textContent = data.lobby.team_size ?? 5;
        }
        if (teamSizeAlt) {
            teamSizeAlt.textContent = data.lobby.team_size ?? 5;
        }

        const currentTeam = data.lobby.current_team;
        const maxTeamSize = data.lobby.team_size ?? 5;
        if (joinCt) {
            joinCt.disabled = (data.lobby.ct_count ?? 0) >= maxTeamSize || lobbyLocked;
        }
        if (joinT) {
            joinT.disabled = (data.lobby.t_count ?? 0) >= maxTeamSize || lobbyLocked;
        }

        if (joinCt && joinT) {
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
        }

        lobbyLocked = data.lobby.locked ?? false;

        if (toggleReadyBtn) {
            toggleReadyBtn.disabled = lobbyLocked;
            const currentUser = users.find(u => u.is_current_user || u.id === @json(Auth::id()));
            if (currentUser && currentUser.is_ready) {
                toggleReadyBtn.textContent = 'NO LISTO';
                toggleReadyBtn.classList.remove('bg-green-600', 'hover:bg-green-500');
                toggleReadyBtn.classList.add('bg-red-600', 'hover:bg-red-500');
            } else {
                toggleReadyBtn.textContent = 'LISTO';
                toggleReadyBtn.classList.remove('bg-red-600', 'hover:bg-red-500');
                toggleReadyBtn.classList.add('bg-green-600', 'hover:bg-green-500');
            }
        }

        if (data.is_ready && !isUnlimited) {
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

    const sendHeartbeat = async () => {
        try {
            await fetch(heartbeatUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
        } catch (e) {}
    };

    const sendLeave = () => {
        if (hasLeft) {
            return;
        }

        if (lobbyLocked) {
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
        if (lobbyLocked) {
            alert('El match ya ha comenzado. No puedes cambiar de equipo.');
            return;
        }

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

    const toggleReady = async () => {
        if (lobbyLocked) return;

        try {
            const response = await fetch(readyUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) return;

            const data = await response.json();
            applyPayload(data);
        } catch (error) {
            // ignore
        }
    };

    if (joinCt) {
        joinCt.addEventListener('click', () => setTeam('ct'));
    }
    if (joinT) {
        joinT.addEventListener('click', () => setTeam('t'));
    }
    if (toggleReadyBtn) {
        toggleReadyBtn.addEventListener('click', toggleReady);
    }

    window.addEventListener('beforeunload', sendLeave);
    window.addEventListener('pagehide', sendLeave);

    setInterval(sendHeartbeat, 10000);

    const echoReady = initEcho();

    if (!echoReady) {
        setInterval(updateLobby, 1000);
    }
})();
</script>
@endsection
