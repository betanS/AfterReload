@extends('layouts.app')

@section('title', __('Lobby'))

@section('content')
@php
    $ctPlayers = $lobby->users->filter(fn ($player) => $player->pivot?->team === 'ct');
    $tPlayers = $lobby->users->filter(fn ($player) => $player->pivot?->team === 't');
    $genericPlayers = $lobby->users;
    $initialLobbyPayload = [
        'server' => [
            'id' => $server->id,
            'name' => $server->name,
            'type' => $server->type,
            'ip' => $server->ip,
            'port' => $server->port,
            'password' => null,
        ],
        'lobby' => [
            'id' => $lobby->id,
            'status' => $lobby->status,
            'is_unlimited' => $isUnlimitedLobby,
            'required_players' => $lobby->required_players,
            'users_count' => $lobby->users_count,
            'missing_players' => $missingPlayers,
            'team_size' => $teamSize,
            'ct_count' => $ctCount,
            't_count' => $tCount,
            'current_team' => $currentTeam ?? null,
            'locked' => ! $isUnlimitedLobby && $lobby->status === 'live' && $lobby->started_at !== null,
        ],
        'is_ready' => $isReady,
        'users' => $lobby->users->map(fn ($user) => [
            'id' => $user->id,
            'name' => $user->steam_nickname ?? $user->name,
            'avatar' => $user->avatar,
            'rank_points' => $user->rank_points,
            'team' => $user->pivot?->team,
            'is_ready' => (bool) $user->pivot?->is_ready,
            'is_current_user' => $user->id === Auth::id(),
        ])->values(),
    ];
@endphp

<div class="app-root min-h-screen bg-[#121212] text-slate-100 p-4 md:p-8">
    <div class="mx-auto max-w-6xl">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('servers.index') }}" class="inline-flex items-center rounded-sm border border-[#222222] bg-[#1b1b1b] px-4 py-2 text-sm font-semibold uppercase tracking-wider text-slate-200 transition hover:border-[#333333]">
                    {{ __('Volver a servidores') }}
                </a>
                <span class="rounded-sm border border-[#222222] bg-[#1b1b1b] px-3 py-2 text-xs font-bold uppercase tracking-widest text-[#5b7cff]">
                    {{ __('Servidor') }} #<span id="lobby-id">{{ $server->id }}</span>
                </span>
            </div>

            @if(! $isUnlimitedLobby)
                <button id="leave-lobby" class="inline-flex items-center rounded-sm border border-red-500/30 bg-red-500/5 px-4 py-2 text-sm font-semibold uppercase tracking-wider text-red-300 transition hover:bg-red-500/10">
                    {{ __('Salir del lobby') }}
                </button>
            @endif
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <section class="rounded-sm border border-[#222222] bg-[#1b1b1b] p-4 shadow-xl md:p-6 lg:col-span-2">
                <h1 class="text-xl font-black uppercase tracking-tight text-white md:text-2xl">{{ $server->name }}</h1>
                <p class="mt-2 text-sm font-semibold uppercase tracking-widest text-slate-400">
                    {{ __('Jugadores en lobby') }}:
                    <span id="players-count" class="font-bold text-[#5b7cff]">{{ $lobby->users_count }}</span>
                    /
                    <span id="required-players">{{ $isUnlimitedLobby ? '∞' : $lobby->required_players }}</span>
                </p>

                @if(! $isUnlimitedLobby)
                    <div class="mt-6 grid gap-4 md:grid-cols-2">
                        <div class="rounded-sm border border-[#5b7cff]/20 bg-[#121212] p-3 md:p-4">
                            <div class="flex items-center justify-between">
                                <h2 class="text-xs font-bold uppercase tracking-widest text-[#5b7cff] md:text-sm">{{ __('Counter-Terrorists') }}</h2>
                                <span class="text-xs font-bold text-slate-500"><span id="ct-count">{{ $ctCount }}</span>/<span id="team-size">{{ $teamSize }}</span></span>
                            </div>
                            <button id="join-ct" class="mt-3 w-full rounded-sm border border-[#5b7cff]/30 bg-[#5b7cff]/5 px-3 py-2 text-[10px] font-bold uppercase tracking-widest text-[#5b7cff] transition hover:bg-[#5b7cff]/10 md:text-xs">
                                {{ __('Unirse a CT') }}
                            </button>
                            <div id="ct-list" class="mt-4 grid gap-2 md:gap-3">
                                @foreach($ctPlayers as $player)
                                    <div class="relative flex items-center gap-3 rounded-sm border border-[#222222] bg-[#1b1b1b] px-3 py-2 md:px-4 md:py-3">
                                        <img src="{{ $player->avatar }}" alt="Avatar {{ $player->steam_nickname ?? $player->name }}" class="h-8 w-8 rounded-sm border border-[#5b7cff]/40 md:h-10 md:w-10">
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-sm font-bold text-white">{{ $player->steam_nickname ?? $player->name }}</p>
                                            <p class="text-[10px] font-semibold uppercase tracking-tighter text-slate-500 md:text-xs">Elo: {{ $player->rank_points }}</p>
                                        </div>
                                        @if($player->pivot?->is_ready)
                                            <span class="absolute right-2 top-2 text-emerald-500" title="{{ __('Listo') }}">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                                            </span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="rounded-sm border border-red-500/20 bg-[#121212] p-3 md:p-4">
                            <div class="flex items-center justify-between">
                                <h2 class="text-xs font-bold uppercase tracking-widest text-red-400 md:text-sm">{{ __('Terrorists') }}</h2>
                                <span class="text-xs font-bold text-slate-500"><span id="t-count">{{ $tCount }}</span>/<span id="team-size-alt">{{ $teamSize }}</span></span>
                            </div>
                            <button id="join-t" class="mt-3 w-full rounded-sm border border-red-500/30 bg-red-500/5 px-3 py-2 text-[10px] font-bold uppercase tracking-widest text-red-300 transition hover:bg-red-500/10 md:text-xs">
                                {{ __('Unirse a T') }}
                            </button>
                            <div id="t-list" class="mt-4 grid gap-2 md:gap-3">
                                @foreach($tPlayers as $player)
                                    <div class="relative flex items-center gap-3 rounded-sm border border-[#222222] bg-[#1b1b1b] px-3 py-2 md:px-4 md:py-3">
                                        <img src="{{ $player->avatar }}" alt="Avatar {{ $player->steam_nickname ?? $player->name }}" class="h-8 w-8 rounded-sm border border-red-500/40 md:h-10 md:w-10">
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-sm font-bold text-white">{{ $player->steam_nickname ?? $player->name }}</p>
                                            <p class="text-[10px] font-semibold uppercase tracking-tighter text-slate-500 md:text-xs">Elo: {{ $player->rank_points }}</p>
                                        </div>
                                        @if($player->pivot?->is_ready)
                                            <span class="absolute right-2 top-2 text-emerald-500" title="{{ __('Listo') }}">
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
                        <p class="mb-4 text-xs font-bold uppercase tracking-[0.2em] text-slate-500">{{ __('Jugadores conectados') }}</p>
                        <div id="generic-list" class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                            @foreach($genericPlayers as $player)
                                <div class="flex items-center gap-3 rounded-sm border border-[#222222] bg-[#1b1b1b] px-3 py-2">
                                    <img src="{{ $player->avatar }}" alt="Avatar {{ $player->steam_nickname ?? $player->name }}" class="h-8 w-8 rounded-sm border border-[#5b7cff]/40">
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-bold text-white">{{ $player->steam_nickname ?? $player->name }}</p>
                                        <p class="text-[10px] font-semibold uppercase tracking-tighter text-slate-500">Elo: {{ $player->rank_points }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </section>

            <aside class="h-fit rounded-sm border border-[#222222] bg-[#1b1b1b] p-4 shadow-xl md:p-6">
                @if($isUnlimitedLobby)
                    <div class="mb-4 rounded-sm border border-[#5b7cff]/20 bg-[#5b7cff]/5 p-4">
                        <p class="mb-1 text-[10px] font-bold uppercase tracking-widest text-[#5b7cff]">{{ __('IP pública del servidor') }}</p>
                        <p id="public-server-address" class="break-all rounded-sm border border-[#222222] bg-[#121212] p-2 font-mono text-sm text-white">{{ $server->ip }}:{{ $server->port }}</p>
                        <p id="public-connect-command" class="mt-2 break-all font-mono text-[10px] text-slate-500 md:text-xs">connect {{ $server->ip }}:{{ $server->port }}</p>
                        <a id="public-join-link" href="steam://connect/{{ $server->ip }}:{{ $server->port }}" class="mt-4 inline-block w-full rounded-sm bg-[#5b7cff] px-4 py-3 text-center text-xs font-bold uppercase tracking-widest text-white shadow-lg shadow-black/40 transition hover:bg-[#7c5cff]">
                            {{ __('Conectar ahora') }}
                        </a>
                    </div>
                @endif

                <div id="ready-panel" class="{{ ($isReady && ! $isUnlimitedLobby) ? '' : 'hidden' }}">
                    <p class="mb-1 text-[10px] font-bold uppercase tracking-widest text-emerald-400 md:text-xs">{{ __('Partida lista') }}</p>
                    <h2 class="text-lg font-black uppercase tracking-tight text-white md:text-xl">{{ __('Servidor desbloqueado') }}</h2>
                    <p class="mt-3 text-sm text-slate-400">{{ __('El match ha sido configurado. Puedes conectarte ahora.') }}</p>

                    <div class="mt-5 rounded-sm border border-[#222222] bg-[#121212] p-4">
                        <p class="mb-1 text-[10px] font-bold uppercase tracking-widest text-slate-500">{{ __('IP del servidor') }}</p>
                        <p id="server-address" class="break-all font-mono text-sm text-white">{{ $server->ip }}:{{ $server->port }}</p>
                        <p id="connect-command" class="mt-2 break-all font-mono text-[10px] text-[#5b7cff] md:text-xs">connect {{ $server->ip }}:{{ $server->port }}</p>
                    </div>

                    <a id="join-match-link" href="steam://connect/{{ $server->ip }}:{{ $server->port }}" class="mt-6 inline-block w-full rounded-sm bg-[#5b7cff] px-4 py-4 text-center text-sm font-bold uppercase tracking-widest text-white shadow-lg shadow-black/40 transition hover:bg-[#7c5cff]">
                        {{ __('CONECTAR AL MATCH') }}
                    </a>
                </div>

                <div id="waiting-panel" class="{{ ($isReady && ! $isUnlimitedLobby) ? 'hidden' : '' }}">
                    @if($isUnlimitedLobby)
                        <p class="mb-1 text-[10px] font-bold uppercase tracking-widest text-[#5b7cff] md:text-xs">{{ __('Estado del servidor') }}</p>
                        <h2 class="text-lg font-black uppercase tracking-tight text-white md:text-xl">{{ __('Servidor Activo') }}</h2>
                        <p class="mt-3 text-sm text-slate-400">{{ __('Servidor público abierto. Conéctate para jugar.') }}</p>
                    @else
                        <p class="mb-1 text-[10px] font-bold uppercase tracking-widest text-[#5b7cff] md:text-xs">{{ __('Esperando jugadores') }}</p>
                        <h2 class="text-lg font-black uppercase tracking-tight text-white md:text-xl">{{ __('En cola para match') }}</h2>

                        <div id="ready-section" class="mt-6">
                            <button id="toggle-ready" class="w-full rounded-sm bg-emerald-600 px-4 py-4 text-center text-sm font-bold uppercase tracking-widest text-white shadow-lg shadow-black/40 transition hover:bg-emerald-500 disabled:opacity-50">
                                {{ __('ESTOY LISTO') }}
                            </button>
                            <p class="mt-3 text-center text-[10px] font-bold uppercase tracking-widest text-slate-500">{{ __('Confirma tu asistencia') }}</p>
                        </div>

                        <p class="mt-8 text-sm font-semibold text-slate-400">
                            {{ __('Faltan') }} <span id="missing-players" class="font-bold text-[#5b7cff]">{{ $missingPlayers }}</span> {{ __('jugadores para iniciar') }}.
                        </p>
                    @endif

                    <div class="mt-6 rounded-sm border border-[#222222] bg-[#121212] p-4">
                        <p class="mb-1 text-[10px] font-bold uppercase tracking-widest text-slate-500">{{ __('Estado') }}</p>
                        <p id="lobby-status" class="text-sm font-bold uppercase tracking-wider text-white">{{ strtoupper($lobby->status) }}</p>
                    </div>

                    <p class="mt-6 flex items-center gap-2 text-[10px] font-bold uppercase tracking-widest text-slate-600">
                        <span class="flex h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        Live Update
                    </p>
                </div>
            </aside>
        </div>
    </div>
</div>

<script>
(() => {
    const csrfToken = @json(csrf_token());
    const authUserId = @json(Auth::id());
    const isUnlimitedLobby = @json($isUnlimitedLobby);

    const urls = {
        status: @json(route('lobby.status', $server)),
        leave: @json(route('lobby.leave', $server)),
        team: @json(route('lobby.team', $server)),
        ready: @json(route('lobby.ready', $server)),
        servers: @json(route('servers.index')),
    };

    const ctList = document.getElementById('ct-list');
    const tList = document.getElementById('t-list');
    const genericList = document.getElementById('generic-list');
    const playersCount = document.getElementById('players-count');
    const requiredPlayers = document.getElementById('required-players');
    const missingPlayers = document.getElementById('missing-players');
    const ctCount = document.getElementById('ct-count');
    const tCount = document.getElementById('t-count');
    const teamSize = document.getElementById('team-size');
    const teamSizeAlt = document.getElementById('team-size-alt');
    const lobbyStatus = document.getElementById('lobby-status');
    const waitingPanel = document.getElementById('waiting-panel');
    const readyPanel = document.getElementById('ready-panel');
    const readySection = document.getElementById('ready-section');
    const serverAddress = document.getElementById('server-address');
    const connectCommand = document.getElementById('connect-command');
    const publicServerAddress = document.getElementById('public-server-address');
    const publicConnectCommand = document.getElementById('public-connect-command');
    const joinMatchLink = document.getElementById('join-match-link');
    const publicJoinLink = document.getElementById('public-join-link');
    const toggleReadyBtn = document.getElementById('toggle-ready');
    const joinCtBtn = document.getElementById('join-ct');
    const joinTBtn = document.getElementById('join-t');
    const leaveLobbyBtn = document.getElementById('leave-lobby');

    let pendingRequest = false;

    const escapeHtml = (value) => String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    const renderPlayers = (container, users, borderClass) => {
        if (!container) {
            return;
        }

        container.innerHTML = users.map((user) => {
            const name = escapeHtml(user.name ?? 'Steam User');
            const avatar = escapeHtml(user.avatar ?? 'https://placehold.co/40x40');
            const rank = escapeHtml(user.rank_points ?? 0);
            const readyBadge = user.is_ready
                ? '<span class="absolute right-2 top-2 text-emerald-500"><svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg></span>'
                : '';

            return `
                <div class="relative flex items-center gap-3 rounded-sm border border-[#222222] bg-[#1b1b1b] px-3 py-2 md:px-4 md:py-3">
                    <img src="${avatar}" alt="Avatar ${name}" class="h-8 w-8 rounded-sm border ${borderClass} md:h-10 md:w-10">
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-bold text-white">${name}</p>
                        <p class="text-[10px] font-semibold uppercase tracking-tighter text-slate-500 md:text-xs">{{ __('Elo') }}: ${rank}</p>
                    </div>
                    ${readyBadge}
                </div>
            `;
        }).join('');
    };

    const setConnectData = (payload) => {
        const address = `${payload.server.ip}:${payload.server.port}`;
        const connectLine = payload.server.password
            ? `connect ${address}; password ${payload.server.password}`
            : `connect ${address}`;

        if (serverAddress) {
            serverAddress.textContent = address;
        }
        if (connectCommand) {
            connectCommand.textContent = connectLine;
        }
        if (publicServerAddress) {
            publicServerAddress.textContent = address;
        }
        if (publicConnectCommand) {
            publicConnectCommand.textContent = connectLine;
        }
        if (joinMatchLink) {
            joinMatchLink.href = `steam://connect/${address}`;
        }
        if (publicJoinLink) {
            publicJoinLink.href = `steam://connect/${address}`;
        }
    };

    const setButtonState = (button, enabled, activeClasses, inactiveClasses) => {
        if (!button) {
            return;
        }

        if (enabled) {
            button.classList.remove(...inactiveClasses);
            button.classList.add(...activeClasses);
        } else {
            button.classList.remove(...activeClasses);
            button.classList.add(...inactiveClasses);
        }
    };

    const applyPayload = (payload) => {
        if (!payload || !payload.lobby || !Array.isArray(payload.users)) {
            return;
        }

        const users = payload.users;
        const lobby = payload.lobby;
        const currentUser = users.find((user) => user.is_current_user || user.id === authUserId) ?? null;
        const lobbyLocked = !!lobby.locked;

        if (playersCount) {
            playersCount.textContent = String(lobby.users_count ?? 0);
        }
        if (requiredPlayers && !lobby.is_unlimited) {
            requiredPlayers.textContent = String(lobby.required_players ?? 0);
        }
        if (missingPlayers) {
            missingPlayers.textContent = String(lobby.missing_players ?? 0);
        }
        if (ctCount) {
            ctCount.textContent = String(lobby.ct_count ?? 0);
        }
        if (tCount) {
            tCount.textContent = String(lobby.t_count ?? 0);
        }
        if (teamSize) {
            teamSize.textContent = String(lobby.team_size ?? 0);
        }
        if (teamSizeAlt) {
            teamSizeAlt.textContent = String(lobby.team_size ?? 0);
        }
        if (lobbyStatus) {
            lobbyStatus.textContent = String(lobby.status ?? '').toUpperCase();
        }

        setConnectData(payload);

        if (!isUnlimitedLobby) {
            const ctUsers = users.filter((user) => user.team === 'ct');
            const tUsers = users.filter((user) => user.team === 't');

            renderPlayers(ctList, ctUsers, 'border-[#5b7cff]/40');
            renderPlayers(tList, tUsers, 'border-red-500/40');

            const currentTeam = currentUser?.team ?? null;
            const canSwitchTeams = !lobbyLocked;

            if (joinCtBtn) {
                joinCtBtn.disabled = !canSwitchTeams || currentTeam === 'ct';
            }
            if (joinTBtn) {
                joinTBtn.disabled = !canSwitchTeams || currentTeam === 't';
            }

            setButtonState(
                joinCtBtn,
                currentTeam === 'ct',
                ['ring-1', 'ring-[#5b7cff]', 'bg-[#5b7cff]/15'],
                ['ring-0']
            );
            setButtonState(
                joinTBtn,
                currentTeam === 't',
                ['ring-1', 'ring-red-500', 'bg-red-500/15'],
                ['ring-0']
            );

            if (toggleReadyBtn) {
                toggleReadyBtn.disabled = lobbyLocked || !currentUser;

                if (currentUser?.is_ready) {
                    toggleReadyBtn.textContent = '{{ __('NO LISTO') }}';
                    toggleReadyBtn.classList.remove('bg-emerald-600', 'hover:bg-emerald-500');
                    toggleReadyBtn.classList.add('bg-red-600', 'hover:bg-red-500');
                } else {
                    toggleReadyBtn.textContent = '{{ __('ESTOY LISTO') }}';
                    toggleReadyBtn.classList.remove('bg-red-600', 'hover:bg-red-500');
                    toggleReadyBtn.classList.add('bg-emerald-600', 'hover:bg-emerald-500');
                }
            }

            if (readySection) {
                readySection.classList.toggle('opacity-50', lobbyLocked);
            }
        } else {
            renderPlayers(genericList, users, 'border-[#5b7cff]/40');
        }

        const showReadyPanel = !isUnlimitedLobby && !!payload.is_ready;
        if (readyPanel) {
            readyPanel.classList.toggle('hidden', !showReadyPanel);
        }
        if (waitingPanel) {
            waitingPanel.classList.toggle('hidden', showReadyPanel);
        }
        if (leaveLobbyBtn) {
            leaveLobbyBtn.disabled = lobbyLocked;
        }
    };

    const sendJson = async (url, body = {}) => {
        if (pendingRequest) {
            return null;
        }

        pendingRequest = true;

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(body),
            });

            const payload = await response.json().catch(() => ({}));

            if (!response.ok) {
                throw new Error(payload.message || 'request-failed');
            }

            return payload;
        } finally {
            pendingRequest = false;
        }
    };

    const refreshStatus = async () => {
        try {
            const response = await fetch(urls.status, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                cache: 'no-store',
            });

            if (response.status === 409) {
                window.location.href = urls.servers;
                return;
            }

            if (!response.ok) {
                throw new Error('status-failed');
            }

            const payload = await response.json();
            applyPayload(payload);
        } catch (_error) {
        }
    };

    if (joinCtBtn) {
        joinCtBtn.addEventListener('click', async () => {
            const payload = await sendJson(urls.team, { team: 'ct' });
            if (payload) {
                applyPayload(payload);
            }
        });
    }

    if (joinTBtn) {
        joinTBtn.addEventListener('click', async () => {
            const payload = await sendJson(urls.team, { team: 't' });
            if (payload) {
                applyPayload(payload);
            }
        });
    }

    if (toggleReadyBtn) {
        toggleReadyBtn.addEventListener('click', async () => {
            const payload = await sendJson(urls.ready);
            if (payload) {
                applyPayload(payload);
            }
        });
    }

    if (leaveLobbyBtn) {
        leaveLobbyBtn.addEventListener('click', async () => {
            const payload = await sendJson(urls.leave);
            if (payload?.left) {
                window.location.href = urls.servers;
            }
        });
    }

    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            refreshStatus();
        }
    });

    applyPayload(@json($initialLobbyPayload));

    window.setInterval(refreshStatus, 3000);
})();
</script>
@endsection
