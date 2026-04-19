<nav class="relative z-50 bg-[#121212] border-b border-[#222222]">
    <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
        <!-- Logo -->
        <div class="flex items-center">
            <a href="{{ route('welcome') }}" class="flex items-center gap-3">
                <img src="{{ asset('branding/minilogoWithBg.png') }}" alt="AfterReload" class="h-10 w-10 rounded-sm">
                <div class="leading-tight hidden sm:block">
                    <p class="text-[10px] uppercase tracking-[0.2em] text-[#5b7cff]">AfterReload</p>
                    <h1 class="text-lg font-black text-white">CSGO Matchmaking</h1>
                </div>
            </a>
        </div>

        <!-- Desktop Navigation & User -->
        <div class="hidden lg:flex items-center gap-6">
            @auth
                <div class="flex items-center gap-4">
                    <a href="{{ route('servers.index') }}" class="inline-flex items-center gap-2 text-sm font-bold text-slate-100 hover:text-[#5b7cff] transition uppercase">
                        <img src="{{ asset('icons/home.svg') }}" alt="Home" class="h-4 w-4 opacity-70">
                        SERVIDORES
                    </a>
                    <a href="{{ route('ranking') }}" class="inline-flex items-center gap-2 text-sm font-bold text-slate-100 hover:text-[#5b7cff] transition uppercase">
                        <img src="{{ asset('icons/ranking.svg') }}" alt="Ranking" class="h-4 w-4 opacity-70">
                        RANKING
                    </a>
                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('store') }}" class="inline-flex items-center gap-2 text-sm font-bold text-slate-100 hover:text-[#5b7cff] transition uppercase">
                            <img src="{{ asset('icons/store.svg') }}" alt="Tienda" class="h-4 w-4 opacity-70">
                            TIENDA
                        </a>
                        <a href="{{ route('admin.index') }}" class="inline-flex items-center gap-2 text-sm font-bold text-[#5b7cff] hover:text-[#7c5cff] transition uppercase">
                            <img src="{{ asset('icons/admin.svg') }}" alt="Admin" class="h-4 w-4">
                            ADMIN
                        </a>
                    @endif
                </div>

                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-3 rounded-sm border border-[#222222] bg-[#1b1b1b] px-3 py-1.5 text-[11px]">
                        <div class="flex flex-col leading-tight">
                            <span class="text-slate-500 uppercase text-[9px] font-bold">Rango</span>
                            <span class="font-bold text-white">{{ auth()->user()->rank_points }}</span>
                        </div>
                        <div class="h-6 w-px bg-[#222222]"></div>
                        <div class="flex flex-col leading-tight">
                            <span class="text-slate-500 uppercase text-[9px] font-bold">Credits</span>
                            <span class="font-bold text-white">{{ auth()->user()->blue_credits }}</span>
                        </div>
                    </div>

                    <div class="relative">
                        <details class="group">
                            <summary class="flex cursor-pointer list-none items-center gap-3 rounded-sm border border-[#222222] bg-[#1b1b1b] px-3 py-1.5 text-sm font-semibold text-slate-100 hover:border-[#333333] transition">
                                <img src="{{ auth()->user()->avatar }}" class="h-7 w-7 rounded-sm border border-[#5b7cff]/60" alt="Avatar">
                                <span>{{ auth()->user()->steam_nickname ?? auth()->user()->name }}</span>
                                <svg class="h-4 w-4 text-[#5b7cff] transition group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </summary>
                            <div class="absolute right-0 mt-2 w-52 rounded-sm border border-[#222222] bg-[#1b1b1b] shadow-2xl z-50 p-1">
                                <a href="{{ route('profile') }}" class="block rounded-sm px-3 py-2 text-sm text-slate-200 hover:bg-[#222222] transition">Mi Perfil</a>
                                <a href="{{ route('inventory') }}" class="block rounded-sm px-3 py-2 text-sm text-slate-200 hover:bg-[#222222] transition">Inventario</a>
                                <div class="h-px bg-[#222222] my-1"></div>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="block w-full rounded-sm px-3 py-2 text-left text-sm text-red-500 hover:bg-[#222222] transition">Cerrar Sesión</button>
                                </form>
                            </div>
                        </details>
                    </div>
                </div>
            @else
                <a href="{{ route('login.steam') }}" class="rounded-sm bg-[#5b7cff] px-6 py-2.5 text-sm font-bold uppercase tracking-widest text-white transition hover:bg-[#7c5cff] shadow-lg shadow-black/50">
                    Iniciar con Steam
                </a>
            @endauth
        </div>

        <!-- Mobile Menu Button -->
        <div class="lg:hidden flex items-center gap-3">
            @auth
                <div class="flex items-center gap-2 rounded-sm border border-[#222222] bg-[#1b1b1b] px-2 py-1 text-[10px]">
                    <span class="font-bold text-[#5b7cff]">{{ auth()->user()->rank_points }} RP</span>
                </div>
            @endauth
            
            <button id="mobile-menu-toggle" class="p-2 text-slate-300 hover:text-white transition">
                <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path id="menu-icon" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7" />
                    <path id="close-icon" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>

    <!-- Mobile Menu Overlay -->
    <div id="mobile-menu" class="hidden lg:hidden border-t border-[#222222] bg-[#121212] px-6 py-8">
        @auth
            <div class="flex flex-col gap-6">
                <div class="flex items-center gap-4 border-b border-[#222222] pb-6">
                    <img src="{{ auth()->user()->avatar }}" class="h-14 w-14 rounded-sm border-2 border-[#5b7cff]" alt="Avatar">
                    <div>
                        <p class="text-xl font-bold text-white">{{ auth()->user()->steam_nickname ?? auth()->user()->name }}</p>
                        <p class="text-xs text-slate-500 uppercase tracking-widest">{{ auth()->user()->role }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-sm border border-[#222222] bg-[#1b1b1b] p-4">
                        <p class="text-[10px] text-slate-500 uppercase font-bold mb-1">Puntos</p>
                        <p class="text-xl font-black text-[#5b7cff]">{{ auth()->user()->rank_points }}</p>
                    </div>
                    <div class="rounded-sm border border-[#222222] bg-[#1b1b1b] p-4">
                        <p class="text-[10px] text-slate-500 uppercase font-bold mb-1">Créditos</p>
                        <p class="text-xl font-black text-white">{{ auth()->user()->blue_credits }}</p>
                    </div>
                </div>

                <nav class="flex flex-col gap-2">
                    <a href="{{ route('servers.index') }}" class="flex items-center gap-4 rounded-sm border border-[#222222] bg-[#1b1b1b] px-5 py-4 font-bold text-slate-200 hover:bg-[#222222]">
                        <img src="{{ asset('icons/home.svg') }}" class="h-5 w-5 opacity-70">
                        SERVIDORES
                    </a>
                    <a href="{{ route('ranking') }}" class="flex items-center gap-4 rounded-sm border border-[#222222] bg-[#1b1b1b] px-5 py-4 font-bold text-slate-200 hover:bg-[#222222]">
                        <img src="{{ asset('icons/ranking.svg') }}" class="h-5 w-5 opacity-70">
                        RANKING
                    </a>
                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('store') }}" class="flex items-center gap-4 rounded-sm border border-[#222222] bg-[#1b1b1b] px-5 py-4 font-bold text-slate-200 hover:bg-[#222222]">
                            <img src="{{ asset('icons/store.svg') }}" class="h-5 w-5 opacity-70">
                            TIENDA
                        </a>
                        <a href="{{ route('admin.index') }}" class="flex items-center gap-4 rounded-sm border border-[#222222] bg-[#1b1b1b] px-5 py-4 font-bold text-[#5b7cff] hover:bg-[#222222]">
                            <img src="{{ asset('icons/admin.svg') }}" class="h-5 w-5 opacity-70">
                            ADMIN PANEL
                        </a>
                    @endif
                </nav>

                <div class="h-px bg-[#222222] my-2"></div>

                <div class="flex flex-col gap-2">
                    <a href="{{ route('profile') }}" class="px-5 py-3 text-slate-400 font-semibold">Mi Perfil</a>
                    <a href="{{ route('inventory') }}" class="px-5 py-3 text-slate-400 font-semibold">Mi Inventario</a>
                    <form method="POST" action="{{ route('logout') }}" class="mt-4">
                        @csrf
                        <button type="submit" class="w-full rounded-sm bg-[#1b1b1b] py-4 font-bold text-red-500">Cerrar Sesión</button>
                    </form>
                </div>
            </div>
        @else
            <a href="{{ route('login.steam') }}" class="block w-full rounded-sm bg-[#5b7cff] py-5 text-center text-lg font-black uppercase tracking-widest text-white">
                INICIAR CON STEAM
            </a>
        @endauth
    </div>
</nav>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.getElementById('mobile-menu-toggle');
    const menu = document.getElementById('mobile-menu');
    const menuIcon = document.getElementById('menu-icon');
    const closeIcon = document.getElementById('close-icon');

    if (toggle && menu) {
        toggle.addEventListener('click', () => {
            const isHidden = menu.classList.contains('hidden');
            menu.classList.toggle('hidden');
            menuIcon.classList.toggle('hidden');
            closeIcon.classList.toggle('hidden');
        });
    }
});
</script>
