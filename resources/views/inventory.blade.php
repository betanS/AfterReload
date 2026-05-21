@extends('layouts.app')

@section('title', __('Inventario'))

@section('content')
<div class="max-w-4xl mx-auto p-4 md:p-8">
    <div class="rounded-sm border border-[#222222] bg-[#1b1b1b] p-8 shadow-2xl relative overflow-hidden flex flex-col items-center justify-center text-center min-h-[420px]">

        <div class="absolute inset-0 flex items-center justify-center opacity-[0.03] pointer-events-none select-none">
            <svg class="h-96 w-96 text-[#5b7cff]" fill="currentColor" viewBox="0 0 24 24"><path d="M11 2v20c-5.07 0-9.22-3.9-9.95-8.83L1 13V9l.05-.17C1.78 3.9 5.93 0 11 0v2h-.1A8.99 8.99 0 0 0 2.1 11H1v2h1.1a8.99 8.99 0 0 0 8.8 8.9V22m2 0c5.07 0 9.22-3.9 9.95-8.83L23 13V9l-.05-.17C22.22 3.9 18.07 0 13 0v2h.1a8.99 8.99 0 0 1 8.8 9H23v2h-1.1a8.99 8.99 0 0 1-8.8 8.9V22z"/></svg>
        </div>

        <div class="relative z-10">
            <span class="inline-block mb-6 px-4 py-1.5 rounded-sm border border-[#5b7cff]/30 bg-[#5b7cff]/10 text-[10px] font-black uppercase tracking-[0.3em] text-[#5b7cff]">
                {{ __('Próximamente') }}
            </span>
            <h2 class="text-4xl font-black uppercase italic tracking-tighter text-white mb-4">{{ __('Inventario') }}</h2>
            <p class="text-sm text-slate-500 font-medium max-w-sm mx-auto">
                {{ __('Estamos trabajando en el sistema de inventario. Pronto podrás gestionar tus skins del servidor desde aquí.') }}
            </p>
        </div>

    </div>
</div>
@endsection
