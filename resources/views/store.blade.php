@extends('layouts.app')

@section('title', __('store'))

@section('content')
<div class="max-w-4xl mx-auto p-4 md:p-8">
    <div class="rounded-sm border border-[#222222] bg-[#1b1b1b] p-8 shadow-2xl relative overflow-hidden">
        <div class="absolute top-0 right-0 p-4 opacity-10">
            <svg class="h-24 w-24 text-[#5b7cff]" fill="currentColor" viewBox="0 0 24 24"><path d="M11 2v20c-5.07 0-9.22-3.9-9.95-8.83L1 13V9l.05-.17C1.78 3.9 5.93 0 11 0v2h-.1A8.99 8.99 0 0 0 2.1 11H1v2h1.1a8.99 8.99 0 0 0 8.8 8.9V22m2 0c5.07 0 9.22-3.9 9.95-8.83L23 13V9l-.05-.17C22.22 3.9 18.07 0 13 0v2h.1a8.99 8.99 0 0 1 8.8 9H23v2h-1.1a8.99 8.99 0 0 1-8.8 8.9V22z"/></svg>
        </div>

        <h2 class="text-3xl font-black uppercase italic tracking-tighter text-white mb-6 border-b border-[#222222] pb-6 relative z-10">{{ __('TIENDA') }}</h2>
        
        <div class="relative z-10">
            <p class="text-base text-slate-300 font-medium leading-relaxed mb-6">
                {{ __('Sección para la implementación de la futura tienda.') }}
            </p>
            
            <div class="bg-[#121212] border-l-4 border-[#5b7cff] p-5 rounded-sm mb-12">
                <p class="text-sm text-slate-400 font-bold uppercase tracking-widest">{{ __('Importante') }}</p>
                <p class="mt-2 text-sm text-slate-300">{{ __('Estas skins son exclusivas de nuestra economía interna y no están vinculadas a tu inventario real de Steam.') }}</p>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                @for($i = 0; $i < 4; $i++)
                <div class="aspect-square bg-[#121212] border border-[#222222] rounded-sm flex flex-col items-center justify-center p-4 group hover:border-[#5b7cff]/40 transition-colors">
                    <div class="h-16 w-16 bg-[#1b1b1b] border border-[#222222] rounded-sm mb-4 flex items-center justify-center opacity-30 group-hover:opacity-60 transition-opacity">
                        <svg class="h-8 w-8 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                    </div>
                    <span class="text-[9px] font-black uppercase tracking-widest text-slate-600">{{ __('Espacio Vacío') }}</span>
                </div>
                @endfor
            </div>
        </div>
    </div>
</div>
@endsection
