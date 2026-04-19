<aside id="main-sidebar" class="fixed inset-y-0 left-0 z-50 w-64 bg-[#1b1b1b] border-r border-[#222222] transform -translate-x-full lg:translate-x-0 lg:static lg:block transition-transform duration-300 ease-in-out shrink-0">
    <div class="flex flex-col h-full">
        <!-- Logo Section -->
        <div class="p-6 border-b border-[#222222] flex items-center justify-between">
            <a href="{{ route('welcome') }}" class="flex items-center gap-3 group">
                <img src="{{ asset('branding/minilogoWithBg.png') }}" alt="AfterReload" class="h-8 w-8 rounded-sm group-hover:scale-105 transition-transform">
                <div class="leading-tight">
                    <p class="text-[9px] uppercase tracking-[0.3em] text-[#5b7cff] font-black">AfterReload</p>
                    <h1 class="text-sm font-black text-white uppercase italic tracking-tighter group-hover:text-[#5b7cff] transition-colors">Matchmaking</h1>
                </div>
            </a>
            
            <!-- Mobile Close Button -->
            <button id="sidebar-close" class="lg:hidden p-2 text-slate-400 hover:text-white transition">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 px-4 py-8 space-y-2 overflow-y-auto">
            <a href="{{ route('servers.index') }}" class="flex items-center gap-4 px-4 py-3 rounded-sm font-bold text-sm transition group {{ request()->routeIs('servers.*') ? 'bg-[#5b7cff] text-white' : 'text-slate-400 hover:bg-[#222222] hover:text-white' }}">
                <img src="{{ asset('icons/home.svg') }}" class="h-5 w-5 opacity-70 group-hover:opacity-100 {{ request()->routeIs('servers.*') ? 'brightness-0 invert' : '' }}">
                <span class="uppercase tracking-widest">{{ __('SERVIDORES') }}</span>
            </a>

            <a href="{{ route('ranking') }}" class="flex items-center gap-4 px-4 py-3 rounded-sm font-bold text-sm transition group {{ request()->routeIs('ranking') ? 'bg-[#5b7cff] text-white' : 'text-slate-400 hover:bg-[#222222] hover:text-white' }}">
                <img src="{{ asset('icons/ranking.svg') }}" class="h-5 w-5 opacity-70 group-hover:opacity-100 {{ request()->routeIs('ranking') ? 'brightness-0 invert' : '' }}">
                <span class="uppercase tracking-widest">{{ __('RANKING') }}</span>
            </a>

            <a href="{{ route('store') }}" class="flex items-center gap-4 px-4 py-3 rounded-sm font-bold text-sm transition group {{ request()->routeIs('store') ? 'bg-[#5b7cff] text-white' : 'text-slate-400 hover:bg-[#222222] hover:text-white' }}">
                <img src="{{ asset('icons/store.svg') }}" class="h-5 w-5 opacity-70 group-hover:opacity-100 {{ request()->routeIs('store') ? 'brightness-0 invert' : '' }}">
                <span class="uppercase tracking-widest">{{ __('TIENDA') }}</span>
            </a>

            <a href="{{ route('contact') }}" class="flex items-center gap-4 px-4 py-3 rounded-sm font-bold text-sm transition group {{ request()->routeIs('contact') ? 'bg-[#5b7cff] text-white' : 'text-slate-400 hover:bg-[#222222] hover:text-white' }}">
                <svg class="h-5 w-5 opacity-70 group-hover:opacity-100 {{ request()->routeIs('contact') ? 'text-white' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                <span class="uppercase tracking-widest">{{ __('Soporte') }}</span>
            </a>

            @if(auth()->check() && auth()->user()->isAdmin())
                <div class="pt-6 pb-2">
                    <span class="px-4 text-[10px] font-black uppercase tracking-[0.3em] text-slate-600">{{ __('Admin Panel') }}</span>
                </div>
                <a href="{{ route('admin.index') }}" class="flex items-center gap-4 px-4 py-3 rounded-sm font-bold text-sm transition group {{ request()->routeIs('admin.*') ? 'bg-[#5b7cff] text-white' : 'text-[#5b7cff] hover:bg-[#5b7cff] hover:text-white' }}">
                    <img src="{{ asset('icons/admin.svg') }}" class="h-5 w-5 brightness-0 invert opacity-70 group-hover:opacity-100 group-hover:invert-0">
                    <span class="uppercase tracking-widest">{{ __('Dashboard') }}</span>
                </a>
            @endif
        </nav>

        <!-- Footer sidebar -->
        <div class="p-4 border-t border-[#222222] space-y-4">
            <!-- Language Toggle -->
            <div class="flex items-center justify-between px-2">
                <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">{{ __('Idioma / Lang') }}</span>
                <div class="flex gap-2">
                    <a href="{{ route('lang.switch', 'es') }}" class="text-[10px] font-black {{ App::getLocale() == 'es' ? 'text-[#5b7cff]' : 'text-slate-600 hover:text-white' }}">ES</a>
                    <span class="text-slate-800">|</span>
                    <a href="{{ route('lang.switch', 'en') }}" class="text-[10px] font-black {{ App::getLocale() == 'en' ? 'text-[#5b7cff]' : 'text-slate-600 hover:text-white' }}">EN</a>
                </div>
            </div>

            <div class="bg-[#121212] p-3 rounded-sm border border-[#222222]">
                <p class="text-[9px] uppercase font-black text-slate-500 tracking-widest mb-1">{{ __('Tu Estado') }}</p>
                <div class="flex items-center gap-2">
                    <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span class="text-[10px] font-bold text-white uppercase tracking-widest">{{ __('Conectado') }}</span>
                </div>
            </div>
            
            <div class="space-y-1">
                <p class="text-[9px] text-center text-slate-600 font-bold uppercase tracking-widest">v2.1.0-beta</p>
                <p class="text-[8px] text-center text-slate-700 font-medium uppercase tracking-tighter">© {{ date('Y') }} AfterReload. {{ __('Todos los derechos reservados.') }}</p>
            </div>
        </div>
    </div>
</aside>
