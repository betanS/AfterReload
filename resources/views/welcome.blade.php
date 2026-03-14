@extends('layouts.app')

@section('title', 'AfterReload')

@section('content')
<div class="app-root min-h-screen bg-slate-950 text-slate-100">
    <div class="relative overflow-hidden">
        <div class="absolute inset-0">
            <div class="absolute inset-0 bg-gradient-to-b from-slate-950/60 via-slate-950 to-slate-950"></div>
            <div class="absolute top-[-200px] left-[-200px] h-[460px] w-[460px] rounded-full bg-blue-500/10 blur-3xl"></div>
            <div class="absolute bottom-[-200px] right-[-200px] h-[460px] w-[460px] rounded-full bg-blue-400/10 blur-3xl"></div>
        </div>

        <div class="relative mx-auto max-w-6xl px-6 py-20 lg:py-28">
            <div class="grid gap-12 lg:grid-cols-[1.2fr,1fr] items-center">
                <div>
                    <div class="inline-flex items-center gap-3 rounded-full border border-blue-500/30 bg-blue-500/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.3em] text-blue-300">
                        AfterReload
                    </div>
                    <h1 class="mt-6 text-4xl font-black text-white sm:text-5xl">CS:GO Matchmaking</h1>
                    <p class="mt-4 text-base text-slate-300 sm:text-lg">
                        Matchmaking comunitario con servidores verificados, lobbies vivos y recompensas por cada victoria.
                    </p>

                    <div class="mt-8 flex flex-wrap gap-4">
                        <a href="{{ route('login.steam') }}" class="rounded-md bg-blue-600 px-6 py-3 text-sm font-bold uppercase tracking-wide text-white transition hover:bg-blue-500">
                            Iniciar con Steam
                        </a>
                        <a href="{{ route('servers.index') }}" class="rounded-md border border-slate-700 px-6 py-3 text-sm font-semibold text-slate-100 hover:border-slate-500">
                            Ver servidores
                        </a>
                    </div>
                </div>

                <div class="relative">
                    <div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-6 shadow-2xl">
                        <div>
                            <p class="text-xs uppercase tracking-[0.2em] text-blue-300">AfterReload</p>
                            <h2 class="text-lg font-black text-white">CSGO Matchmaking</h2>
                        </div>
                        <div class="mt-6 space-y-3 text-sm text-slate-300">
                            <p>✅ Matchmaking rapido y transparente</p>
                            <p>✅ Lobbies con acceso dinamico</p>
                            <p>✅ Ranking y economia interna</p>
                        </div>
                        <div class="mt-6 rounded-xl border border-blue-500/30 bg-blue-500/10 p-4">
                            <p class="text-xs text-blue-200">Servidor destacado</p>
                            <p class="mt-2 text-lg font-semibold text-white">Matchmaking Europa #1</p>
                            <p class="text-xs text-blue-200">Lobbies activos ahora</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-16 grid gap-6 md:grid-cols-3">
                <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-6">
                    <p class="text-xs uppercase tracking-[0.2em] text-blue-300">Lobbies</p>
                    <h3 class="mt-3 text-lg font-bold text-white">Control total</h3>
                    <p class="mt-2 text-sm text-slate-400">Gestiona jugadores, IPs y horarios con visibilidad instantanea.</p>
                </div>
                <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-6">
                    <p class="text-xs uppercase tracking-[0.2em] text-blue-300">Ranking</p>
                    <h3 class="mt-3 text-lg font-bold text-white">Recompensas reales</h3>
                    <p class="mt-2 text-sm text-slate-400">Suma puntos, sube de rango y domina la temporada.</p>
                </div>
                <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-6">
                    <p class="text-xs uppercase tracking-[0.2em] text-blue-300">Economia</p>
                    <h3 class="mt-3 text-lg font-bold text-white">Skins del servidor</h3>
                    <p class="mt-2 text-sm text-slate-400">Una tienda dedicada a skins exclusivas del servidor.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
