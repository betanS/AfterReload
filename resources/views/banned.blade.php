@extends('layouts.app')

@section('title', __('Cuenta bloqueada'))

@section('content')
<div class="flex min-h-[60vh] items-center justify-center p-6">
    <div class="w-full max-w-md rounded-sm border border-[#222222] bg-[#1b1b1b] p-10 text-center shadow-2xl">
        <div class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-sm border border-red-500/20 bg-red-500/10">
            <svg class="h-10 w-10 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
        </div>
        
        <p class="text-[10px] uppercase font-black tracking-[0.3em] text-[#5b7cff] mb-2">{{ __('Cuenta bloqueada') }}</p>
        <h2 class="text-3xl font-black uppercase italic tracking-tighter text-white">{{ __('Acceso Denegado') }}</h2>
        
        <p class="mt-6 text-sm font-medium leading-relaxed text-slate-400">
            {{ __('Tu cuenta ha sido suspendida por incumplir las normas de la comunidad AfterReload.') }}
        </p>

        <div class="mt-10 pt-8 border-t border-[#222222]">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full rounded-sm bg-[#5b7cff] px-10 py-4 text-xs font-black uppercase tracking-widest text-white transition hover:bg-[#7c5cff] shadow-lg shadow-black/20">
                    {{ __('Cerrar Sesión') }}
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
