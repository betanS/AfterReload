@extends('layouts.app')

@section('title', 'AfterReload')

@section('content')
@if(session('auth_error'))
<div class="fixed top-4 left-1/2 -translate-x-1/2 z-50 flex items-center gap-3 rounded-sm border border-red-500/30 bg-red-500/10 px-5 py-3 text-sm font-bold text-red-400 shadow-lg">
    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
    {{ __('No se pudo iniciar sesión con Steam. Inténtalo de nuevo.') }}
</div>
@endif

<div class="app-root min-h-screen bg-[#121212] text-slate-100 relative overflow-hidden">
    <div class="absolute inset-0 z-0 opacity-[0.03]" style="background-image: radial-gradient(#ffffff 1px, transparent 1px); background-size: 40px 40px;"></div>
    <div class="absolute inset-0 z-0 bg-gradient-to-b from-transparent via-[#121212]/50 to-[#121212]"></div>

    <div class="relative z-10">
        <div class="mx-auto max-w-6xl px-6 py-20 lg:py-32">
            <div class="grid gap-16 lg:grid-cols-[1.3fr,1fr] items-center">
                <div>
                    <div class="inline-flex items-center gap-3 rounded-sm border border-[#5b7cff]/30 bg-[#5b7cff]/10 px-4 py-2 text-[10px] font-black uppercase tracking-[0.4em] text-[#5b7cff] mb-8">
                        Matchmaking Engine v2.0
                    </div>
                    <h1 class="text-5xl lg:text-7xl font-black text-white uppercase tracking-tighter leading-[0.9] italic">
                        {{ __('La nueva era del') }} <span class="text-[#5b7cff] not-italic">CS:GO</span> {{ __('competitivo') }}
                    </h1>
                    <p class="mt-8 text-lg lg:text-xl text-slate-400 font-medium max-w-xl leading-relaxed">
                        {{ __('Servidores dedicados de alto rendimiento, sistema de Elo avanzado y una economía real. Únete a la comunidad de AfterReload.') }}
                    </p>

                    <div class="mt-12 flex flex-wrap gap-6">
                        @guest
                            <a href="{{ route('login.steam') }}" class="group relative rounded-sm bg-[#5b7cff] px-10 py-5 text-sm font-black uppercase tracking-widest text-white transition-all hover:bg-[#7c5cff] shadow-[0_0_30px_-5px_rgba(91,124,255,0.35)]">
                                <span class="relative z-10 flex items-center gap-2">
                                    {{ __('Comenzar ahora') }}
                                    <svg class="h-4 w-4 transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
                                </span>
                            </a>
                        @else
                            <a href="{{ route('servers.index') }}" data-spa-link class="group relative rounded-sm bg-[#5b7cff] px-10 py-5 text-sm font-black uppercase tracking-widest text-white transition-all hover:bg-[#7c5cff]">
                                <span class="relative z-10 flex items-center gap-2">
                                    {{ __('Ir a Servidores') }}
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
                                </span>
                            </a>
                        @endguest
                        <a href="#features" class="rounded-sm border border-[#222222] bg-[#1b1b1b]/50 backdrop-blur-sm px-10 py-5 text-sm font-black uppercase tracking-widest text-white transition hover:bg-[#222222]">
                            {{ __('Saber más') }}
                        </a>
                    </div>
                    
                    <div class="mt-20 grid grid-cols-3 gap-8 border-t border-[#222222] pt-12 max-w-lg">
                        <div>
                            <p class="text-2xl font-black text-white italic">{{ $userCount }}</p>
                            <p class="text-[10px] uppercase font-bold text-slate-500 tracking-widest">{{ __('Jugadores') }}</p>
                        </div>
                        <div>
                            <p class="text-2xl font-black text-white italic">24/7</p>
                            <p class="text-[10px] uppercase font-bold text-slate-500 tracking-widest">{{ __('Uptime') }}</p>
                        </div>
                        <div>
                            <p class="text-2xl font-black text-[#5b7cff] italic">Low</p>
                            <p class="text-[10px] uppercase font-bold text-slate-500 tracking-widest">{{ __('Latencia') }}</p>
                        </div>
                    </div>
                </div>

                <div class="relative hidden lg:block">
                    <div class="rounded-sm border border-[#222222] bg-[#1b1b1b] shadow-[0_0_100px_-20px_rgba(0,0,0,0.8)] overflow-hidden relative z-10">
                        <div class="h-8 bg-[#222222] flex items-center gap-2 px-4 border-b border-[#333333]">
                            <div class="h-2 w-2 rounded-full bg-red-500/50"></div>
                            <div class="h-2 w-2 rounded-full bg-yellow-500/50"></div>
                            <div class="h-2 w-2 rounded-full bg-emerald-500/50"></div>
                        </div>
                        <div class="p-8">
                            <div class="flex items-center justify-between mb-8">
                                <div class="h-4 w-32 bg-[#222222] rounded-sm"></div>
                                <div class="h-4 w-12 bg-[#5b7cff]/20 rounded-sm"></div>
                            </div>
                            <div class="space-y-4">
                                @for($i = 0; $i < 4; $i++)
                                <div class="flex items-center gap-4 border-b border-[#222222] pb-4">
                                    <div class="h-10 w-10 bg-[#121212] border border-[#222222] rounded-sm"></div>
                                    <div class="flex-1 space-y-2">
                                        <div class="h-3 w-2/3 bg-[#222222] rounded-sm"></div>
                                        <div class="h-2 w-1/3 bg-[#222222]/50 rounded-sm"></div>
                                    </div>
                                    <div class="h-6 w-16 bg-[#5b7cff] rounded-sm"></div>
                                </div>
                                @endfor
                            </div>
                        </div>
                    </div>
                    
                    <div class="absolute -inset-4 bg-[#5b7cff]/5 blur-3xl rounded-full"></div>
                </div>
            </div>
        </div>

        <div id="features" class="bg-[#1b1b1b]/50 py-32 border-y border-[#222222]">
            <div class="mx-auto max-w-6xl px-6">
                <div class="text-center mb-20">
                    <p class="text-[10px] uppercase font-black text-[#5b7cff] tracking-[0.5em] mb-4">{{ __('Ecosistema') }}</p>
                    <h2 class="text-4xl font-black text-white uppercase italic tracking-tighter">{{ __('Diseñado para competir') }}</h2>
                </div>
                
                <div class="grid gap-12 md:grid-cols-3">
                    <div class="flex flex-col items-center text-center group opacity-70">
                        <div class="relative h-16 w-16 bg-[#121212] border border-[#222222] rounded-sm flex items-center justify-center mb-8 transition-colors">
                            <svg class="h-8 w-8 text-[#5b7cff]/50" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A10.003 10.003 0 0012 3v12.5a2.5 2.5 0 01-5 0c0-.88.363-1.673.945-2.239l.003-.003z" /></svg>
                        </div>
                        <div class="flex items-center gap-2 mb-4">
                            <h3 class="text-xl font-black text-white uppercase italic tracking-tight">Anti-Cheat System</h3>
                            <span class="text-[9px] font-black uppercase tracking-widest text-[#5b7cff] border border-[#5b7cff]/30 bg-[#5b7cff]/10 px-1.5 py-0.5 rounded-sm">{{ __('Próximamente') }}</span>
                        </div>
                        <p class="text-slate-500 leading-relaxed font-medium">{{ __('Algoritmos avanzados de detección para asegurar que cada partida sea justa y competitiva.') }}</p>
                    </div>

                    <div class="flex flex-col items-center text-center group">
                        <div class="h-16 w-16 bg-[#121212] border border-[#222222] rounded-sm flex items-center justify-center mb-8 group-hover:border-[#5b7cff] transition-colors">
                            <svg class="h-8 w-8 text-[#5b7cff]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                        </div>
                        <h3 class="text-xl font-black text-white uppercase mb-4 italic tracking-tight">{{ __('Elo Dinámico') }}</h3>
                        <p class="text-slate-500 leading-relaxed font-medium">{{ __('Gana puntos basados en tu rendimiento individual y colectivo en cada match oficial.') }}</p>
                    </div>

                    <div class="flex flex-col items-center text-center group opacity-70">
                        <div class="h-16 w-16 bg-[#121212] border border-[#222222] rounded-sm flex items-center justify-center mb-8 transition-colors">
                            <svg class="h-8 w-8 text-[#5b7cff]/50" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" /></svg>
                        </div>
                        <div class="flex items-center gap-2 mb-4">
                            <h3 class="text-xl font-black text-white uppercase italic tracking-tight">{{ __('Skins & Rewards') }}</h3>
                            <span class="text-[9px] font-black uppercase tracking-widest text-[#5b7cff] border border-[#5b7cff]/30 bg-[#5b7cff]/10 px-1.5 py-0.5 rounded-sm">{{ __('Próximamente') }}</span>
                        </div>
                        <p class="text-slate-500 leading-relaxed font-medium">{{ __('Utiliza tus créditos ganados para desbloquear cosméticos exclusivos en nuestra tienda interna.') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
