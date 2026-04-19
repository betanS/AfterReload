<header class="bg-[#121212]/80 backdrop-blur-md border-b border-[#222222] sticky top-0 z-40">
    <div class="mx-auto flex h-16 items-center justify-between px-6">
        <!-- Mobile Menu Toggle -->
        <button id="sidebar-toggle" class="p-2 lg:hidden text-slate-400 hover:text-white transition">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7" />
            </svg>
        </button>

        <!-- Search / Info Placeholder (Solo escritorio) -->
        <div class="hidden md:flex items-center gap-6">
            <div class="flex items-center gap-2 bg-[#1b1b1b] border border-[#222222] px-3 py-1.5 rounded-sm">
                <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">{{ __('Global Elo Avg') }}</span>
                <span class="text-[10px] font-black text-[#5b7cff]">{{ $globalEloAvg }} RP</span>
            </div>
        </div>

        <!-- User Section -->
        <div class="flex items-center gap-6">
            @auth
                <!-- User Credits & Stats -->
                <div class="hidden sm:flex items-center gap-4 border-r border-[#222222] pr-6 mr-0">
                    <div class="flex flex-col items-end">
                        <span class="text-[9px] font-black text-slate-500 uppercase tracking-[0.2em]">{{ __('Créditos') }}</span>
                        <span class="text-xs font-black text-white italic">{{ auth()->user()->blue_credits }} CR</span>
                    </div>
                </div>

                <!-- Profile Dropdown -->
                <div class="relative">
                    <details class="group">
                        <summary class="flex cursor-pointer list-none items-center gap-3 hover:opacity-80 transition">
                            <div class="text-right hidden sm:block leading-none">
                                <p class="text-xs font-black text-white uppercase tracking-tighter">{{ auth()->user()->steam_nickname ?? auth()->user()->name }}</p>
                                <p class="text-[9px] font-bold text-[#5b7cff] uppercase tracking-widest mt-0.5">{{ auth()->user()->rank_points }} RP</p>
                            </div>
                            <img src="{{ auth()->user()->avatar }}" class="h-9 w-9 rounded-sm border-2 border-[#5b7cff]/60" alt="Avatar">
                            <svg class="h-4 w-4 text-slate-500 transition group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                        </summary>
                        <div class="absolute right-0 mt-3 w-52 rounded-sm border border-[#222222] bg-[#1b1b1b] shadow-2xl z-50 p-1">
                            <a href="{{ route('profile') }}" class="flex items-center gap-3 rounded-sm px-4 py-3 text-[11px] font-bold text-slate-300 uppercase tracking-widest hover:bg-[#222222] hover:text-[#5b7cff] transition">
                                {{ __('Mi Perfil') }}
                            </a>
                            <a href="{{ route('inventory') }}" class="flex items-center gap-3 rounded-sm px-4 py-3 text-[11px] font-bold text-slate-300 uppercase tracking-widest hover:bg-[#222222] hover:text-[#5b7cff] transition">
                                {{ __('Inventario') }}
                            </a>
                            <div class="h-px bg-[#222222] my-1"></div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full flex items-center gap-3 rounded-sm px-4 py-3 text-[11px] font-bold text-red-500 uppercase tracking-widest hover:bg-red-500/10 transition">
                                    {{ __('Cerrar Sesión') }}
                                </button>
                            </form>
                        </div>
                    </details>
                </div>
            @else
                <a href="{{ route('login.steam') }}" class="rounded-sm bg-[#5b7cff] px-5 py-2 text-[11px] font-black uppercase tracking-widest text-white transition hover:bg-[#7c5cff]">
                    {{ __('Iniciar con Steam') }}
                </a>
            @endauth
        </div>
    </div>
</header>
