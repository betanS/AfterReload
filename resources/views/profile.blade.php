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
            <h3 class="text-xs font-bold uppercase tracking-[0.3em] text-red-500/70 mb-4">{{ __('Zona de peligro') }}</h3>
            <p class="text-xs text-slate-500 mb-4">{{ __('Esta acción eliminará tu cuenta y todos tus datos permanentemente. No se puede deshacer.') }}</p>
            <button id="delete-account-btn" type="button" class="rounded-sm border border-red-800 bg-red-950/30 px-6 py-3 text-xs font-black uppercase tracking-widest text-red-500 hover:bg-red-900/40 transition">
                {{ __('Eliminar mi cuenta') }}
            </button>
        </div>
    </div>
</div>

<div id="delete-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-[#121212]/95 backdrop-blur-md px-6">
    <div class="w-full max-w-md rounded-sm border border-red-900 bg-[#1b1b1b] p-8 shadow-2xl">
        <h3 class="text-lg font-black uppercase italic tracking-tight text-white mb-3">{{ __('¿Eliminar cuenta?') }}</h3>
        <p class="text-sm text-slate-400 mb-8">{{ __('Se borrarán todos tus datos, puntos y posición en el ranking. Esta acción es irreversible.') }}</p>
        <div class="flex gap-3">
            <form method="POST" action="{{ route('account.delete') }}" class="flex-1">
                @csrf
                @method('DELETE')
                <button type="submit" class="w-full rounded-sm bg-red-700 px-6 py-3 text-xs font-black uppercase tracking-widest text-white hover:bg-red-600 transition">
                    {{ __('Sí, eliminar') }}
                </button>
            </form>
            <button id="delete-cancel" type="button" class="flex-1 rounded-sm border border-[#222222] bg-[#1b1b1b] px-6 py-3 text-xs font-black uppercase tracking-widest text-white hover:bg-[#222222] transition">
                {{ __('Cancelar') }}
            </button>
        </div>
    </div>
</div>

<script>
(() => {
    const modal = document.getElementById('delete-modal');
    document.getElementById('delete-account-btn').addEventListener('click', () => {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    });
    document.getElementById('delete-cancel').addEventListener('click', () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    });
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    });
})();
</script>
@endsection
