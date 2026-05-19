@extends('layouts.app')

@section('title', __('Mi Perfil'))

@section('content')
<div class="max-w-4xl mx-auto p-8">
    <div class="rounded-sm border border-[#222222] bg-[#1b1b1b] p-8 shadow-2xl">
        <h2 class="text-3xl font-black uppercase tracking-tighter italic text-white mb-8 border-b border-[#222222] pb-6">{{ __('Mi Perfil') }}</h2>
        
        <div class="flex flex-col md:flex-row items-center gap-8">
            <div>
                <img src="{{ auth()->user()->avatar }}" class="h-24 w-24 rounded-sm border-2 border-[#5b7cff] shadow-lg shadow-[#5b7cff]/20" alt="Avatar">
            </div>
            
            <div class="text-center md:text-left">
                <p class="text-2xl font-black text-white uppercase tracking-tight">{{ auth()->user()->steam_nickname ?? auth()->user()->name }}</p>
                <div class="mt-2 flex flex-wrap justify-center md:justify-start gap-3">
                    <span class="px-3 py-1 bg-[#121212] border border-[#222222] rounded-sm text-[10px] font-bold uppercase tracking-widest text-slate-400">
                        {{ auth()->user()->role }}
                    </span>
                    <span class="px-3 py-1 bg-emerald-500/10 border border-emerald-500/20 rounded-sm text-[10px] font-bold uppercase tracking-widest text-emerald-400">
                        {{ __('Steam Verificado') }}
                    </span>
                </div>
            </div>
        </div>

        <div class="mt-12 grid gap-4 sm:grid-cols-2">
            <div class="rounded-sm border border-[#222222] bg-[#121212] p-6 group hover:border-[#5b7cff]/40 transition">
                <p class="text-[10px] uppercase font-bold tracking-[0.2em] text-[#5b7cff] mb-2">{{ __('Puntos de rango') }}</p>
                <div class="flex items-end gap-2">
                    <p class="text-3xl font-black text-white italic tracking-tighter">{{ auth()->user()->points }}</p>
                    <p class="text-xs text-slate-500 font-bold mb-1 uppercase tracking-widest">ELO</p>
                </div>
            </div>
            
            <div class="rounded-sm border border-[#222222] bg-[#121212] p-6 group hover:border-white/20 transition">
                <p class="text-[10px] uppercase font-bold tracking-[0.2em] text-slate-500 mb-2">{{ __('Créditos AfterReload') }}</p>
                <div class="flex items-end gap-2">
                    <p class="text-3xl font-black text-white italic tracking-tighter">{{ auth()->user()->credits }}</p>
                    <p class="text-xs text-slate-500 font-bold mb-1 uppercase tracking-widest">CR</p>
                </div>
            </div>
        </div>
        
        <div class="mt-12 pt-8 border-t border-[#222222]">
            <h3 class="text-xs font-bold uppercase tracking-[0.3em] text-slate-500 mb-6">{{ __('Estadísticas de Temporada') }}</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-[#121212] p-4 border border-[#222222] rounded-sm">
                    <p class="text-[9px] uppercase font-bold text-slate-600 tracking-widest">{{ __('Partidas') }}</p>
                    <p class="text-lg font-black text-white">0</p>
                </div>
                <div class="bg-[#121212] p-4 border border-[#222222] rounded-sm">
                    <p class="text-[9px] uppercase font-bold text-slate-600 tracking-widest">{{ __('Win Rate') }}</p>
                    <p class="text-lg font-black text-emerald-500">0%</p>
                </div>
                <div class="bg-[#121212] p-4 border border-[#222222] rounded-sm">
                    <p class="text-[9px] uppercase font-bold text-slate-600 tracking-widest">{{ __('K/D Ratio') }}</p>
                    <p class="text-lg font-black text-white">0.00</p>
                </div>
                <div class="bg-[#121212] p-4 border border-[#222222] rounded-sm">
                    <p class="text-[9px] uppercase font-bold text-slate-600 tracking-widest">{{ __('MVP\'s') }}</p>
                    <p class="text-lg font-black text-[#5b7cff]">0</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
