<nav class="relative z-50 bg-slate-950/90 border-b border-slate-800">
    <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
        <div class="flex items-center gap-4">
            <a href="{{ route('welcome') }}" class="flex items-center gap-3">
                <img src="{{ asset('branding/minilogoWithBg.png') }}" alt="AfterReload" class="h-10 w-10 rounded-lg">
                <div class="leading-tight">
                    <p class="text-sm uppercase tracking-[0.2em] text-blue-300">AfterReload</p>
                    <h1 class="text-xl font-black text-white">CSGO Matchmaking</h1>
                </div>
            </a>

            @auth
                <div class="flex items-center gap-3">
                    <a href="{{ route('servers.index') }}" class="inline-flex items-center gap-2 rounded-md border border-slate-800 px-4 py-2 text-base font-semibold text-slate-100 hover:border-slate-700">
                        <img src="{{ asset('icons/home.svg') }}" alt="Home" class="h-4 w-4">
                        Servidores
                    </a>
                    <a href="{{ route('ranking') }}" class="inline-flex items-center gap-2 rounded-md border border-slate-800 px-4 py-2 text-base font-semibold text-slate-100 hover:border-slate-700">
                        <img src="{{ asset('icons/ranking.svg') }}" alt="Ranking" class="h-4 w-4">
                        Ranking
                    </a>
                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('store') }}" class="inline-flex items-center gap-2 rounded-md border border-slate-800 px-4 py-2 text-base font-semibold text-slate-100 hover:border-slate-700">
                            <img src="{{ asset('icons/store.svg') }}" alt="Tienda" class="h-4 w-4">
                            Tienda
                        </a>
                        <a href="{{ route('admin.index') }}" class="inline-flex items-center gap-2 rounded-md border border-slate-800 px-4 py-2 text-base font-semibold text-blue-300 hover:border-slate-700">
                            <img src="{{ asset('icons/admin.svg') }}" alt="Admin" class="h-4 w-4">
                            Admin
                        </a>
                    @endif
                </div>
            @endauth
        </div>

        <div class="flex items-center gap-3">
            @auth
                <div class="flex items-center gap-2 rounded-lg border border-slate-800 bg-slate-900 px-3 py-2 text-[11px] text-slate-200">
                    <div class="flex flex-col leading-tight">
                        <span class="text-blue-300">Rango</span>
                        <span class="font-semibold">{{ auth()->user()->rank_points }}</span>
                    </div>
                    <div class="h-8 w-px bg-slate-800"></div>
                    <div class="flex flex-col leading-tight">
                        <span class="text-blue-300">Credits</span>
                        <span class="font-semibold">{{ auth()->user()->blue_credits }}</span>
                    </div>
                </div>

                <div class="relative">
                    <details class="group">
                        <summary class="flex cursor-pointer list-none items-center gap-3 rounded-full border border-slate-800 bg-slate-900 px-3 py-2 text-sm font-semibold text-slate-100 hover:border-slate-700">
                            <img src="{{ auth()->user()->avatar }}" class="h-7 w-7 rounded-full border border-blue-500/60" alt="Avatar">
                            <span>{{ auth()->user()->steam_nickname ?? auth()->user()->name }}</span>
                            <span class="text-blue-400 transition group-open:rotate-180">v</span>
                        </summary>
                        <div class="absolute right-0 mt-2 w-52 rounded-lg border border-slate-800 bg-slate-950/95 p-2 shadow-xl z-50">
                            <div class="px-3 pb-2 text-[10px] uppercase tracking-[0.2em] text-slate-500">Rol: <span class="{{ auth()->user()->isAdmin() ? 'text-red-400' : '' }}">{{ auth()->user()->role }}</span></div>
                            <a href="{{ route('profile') }}" class="block rounded-md px-3 py-2 text-sm text-slate-200 hover:bg-slate-800">Perfil</a>
                            <a href="{{ route('inventory') }}" class="block rounded-md px-3 py-2 text-sm text-slate-200 hover:bg-slate-800">Inventario</a>
                            <form method="POST" action="{{ route('logout') }}" class="mt-1">
                                @csrf
                                <button type="submit" class="block w-full rounded-md px-3 py-2 text-left text-sm text-slate-200 hover:bg-slate-800">Cerrar sesion</button>
                            </form>
                        </div>
                    </details>
                </div>
            @else
                <a href="{{ route('login.steam') }}" class="rounded-md bg-blue-600 px-5 py-2 text-base font-bold uppercase tracking-wide text-white transition hover:bg-blue-500">
                    Iniciar con Steam
                </a>
            @endauth
        </div>
    </div>
</nav>
