<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AfterReload</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=space-grotesk:400,500,600,700" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Space Grotesk', ui-sans-serif, system-ui, sans-serif; }
    </style>
</head>
<body class="min-h-screen bg-slate-950 text-slate-100">
    <div class="pointer-events-none fixed inset-0 -z-10 overflow-hidden">
        <div class="absolute -top-24 -left-24 h-80 w-80 rounded-full bg-blue-600/20 blur-3xl"></div>
        <div class="absolute top-40 -right-20 h-96 w-96 rounded-full bg-slate-500/20 blur-3xl"></div>
    </div>

    <header class="border-b border-slate-800/80 bg-slate-900/70 backdrop-blur">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
            <div>
                <p class="text-xs uppercase tracking-[0.2em] text-blue-400">AfterReload</p>
                <h1 class="text-lg font-black text-white">CSGO Matchmaking</h1>
            </div>

            @auth
                <a href="{{ route('home') }}" class="rounded-md bg-blue-600 px-5 py-2 text-sm font-bold uppercase tracking-wide transition hover:bg-blue-500">
                    Ir al Home
                </a>
            @else
                <a href="{{ route('login.steam') }}" class="rounded-md bg-blue-600 px-5 py-2 text-sm font-bold uppercase tracking-wide transition hover:bg-blue-500">
                    Iniciar con Steam
                </a>
            @endauth
        </div>
    </header>

    <main class="mx-auto max-w-6xl px-6 py-12 md:py-20">
        @if (session('auth_error'))
            <div class="mb-6 rounded-lg border border-red-400/40 bg-red-500/10 px-4 py-3 text-sm text-red-200">
                {{ session('auth_error') }}
            </div>
        @endif

        <section class="grid items-center gap-10 md:grid-cols-2">
            <div>
                <p class="mb-4 inline-flex rounded-full border border-blue-500/40 bg-blue-500/10 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-blue-300">
                    Lobbies privados y balanceados
                </p>
                <h2 class="text-4xl font-black leading-tight md:text-5xl text-white">
                    Encuentra partidas serias en segundos
                </h2>
                <p class="mt-5 max-w-xl text-slate-300">
                    Inicia sesion con Steam, entra a lobbies activos y juega en servidores dedicados con estado en tiempo real.
                </p>

                <div class="mt-8 flex flex-wrap gap-3">
                    @auth
                        <a href="{{ route('home') }}" class="rounded-md bg-blue-600 px-6 py-3 text-sm font-bold uppercase tracking-wide transition hover:bg-blue-500">
                            Entrar al panel
                        </a>
                    @else
                        <a href="{{ route('login.steam') }}" class="rounded-md bg-blue-600 px-6 py-3 text-sm font-bold uppercase tracking-wide transition hover:bg-blue-500">
                            Conectar Steam
                        </a>
                    @endauth
                    <a href="#como-funciona" class="rounded-md border border-slate-700 px-6 py-3 text-sm font-bold uppercase tracking-wide text-slate-200 transition hover:border-slate-500">
                        Como funciona
                    </a>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6 shadow-2xl shadow-black/30">
                <h3 class="mb-4 text-lg font-bold">Estado del sistema</h3>
                <div class="space-y-3 text-sm">
                    <div class="flex items-center justify-between rounded-lg border border-slate-800 bg-slate-950/70 px-4 py-3">
                        <span class="text-slate-300">Autenticacion Steam</span>
                        <span class="font-semibold text-blue-400">Operativo</span>
                    </div>
                    <div class="flex items-center justify-between rounded-lg border border-slate-800 bg-slate-950/70 px-4 py-3">
                        <span class="text-slate-300">Lobbies competitivos</span>
                        <span class="font-semibold text-blue-400">Disponibles</span>
                    </div>
                    <div class="flex items-center justify-between rounded-lg border border-slate-800 bg-slate-950/70 px-4 py-3">
                        <span class="text-slate-300">Servidores Europa</span>
                        <span class="font-semibold text-blue-300">Monitoreando</span>
                    </div>
                </div>
            </div>
        </section>

        <section id="como-funciona" class="mt-14 grid gap-4 md:grid-cols-3">
            <article class="rounded-xl border border-slate-800 bg-slate-900/70 p-5">
                <p class="text-xs font-bold uppercase tracking-widest text-blue-400">Paso 1</p>
                <h4 class="mt-2 text-lg font-bold">Login con Steam</h4>
                <p class="mt-2 text-sm text-slate-300">Autenticacion sin formularios, con tu identidad oficial de Steam.</p>
            </article>
            <article class="rounded-xl border border-slate-800 bg-slate-900/70 p-5">
                <p class="text-xs font-bold uppercase tracking-widest text-blue-400">Paso 2</p>
                <h4 class="mt-2 text-lg font-bold">Seleccion de servidor</h4>
                <p class="mt-2 text-sm text-slate-300">Consulta disponibilidad, capacidad y estado de cada servidor.</p>
            </article>
            <article class="rounded-xl border border-slate-800 bg-slate-900/70 p-5">
                <p class="text-xs font-bold uppercase tracking-widest text-blue-400">Paso 3</p>
                <h4 class="mt-2 text-lg font-bold">Unete al lobby</h4>
                <p class="mt-2 text-sm text-slate-300">Accede a una partida balanceada y lista para competir.</p>
            </article>
        </section>
    </main>
</body>
</html>