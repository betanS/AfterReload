@extends('layouts.app')

@section('title', 'Contacto')

@section('content')
<div class="max-w-4xl mx-auto p-8">
    <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-6">
        <h2 class="text-2xl font-black mb-4">Contacto</h2>
        <p class="text-sm text-slate-300">Escríbenos para soporte, colaboraciones o dudas sobre la plataforma.</p>

        <form class="mt-6 grid gap-4">
            <div>
                <label class="text-xs uppercase tracking-widest text-blue-300">Nombre</label>
                <input type="text" class="mt-2 w-full rounded-md border border-slate-800 bg-slate-950/70 p-3 text-sm text-slate-100" placeholder="Tu nombre">
            </div>
            <div>
                <label class="text-xs uppercase tracking-widest text-blue-300">Email</label>
                <input type="email" class="mt-2 w-full rounded-md border border-slate-800 bg-slate-950/70 p-3 text-sm text-slate-100" placeholder="tu@email.com">
            </div>
            <div>
                <label class="text-xs uppercase tracking-widest text-blue-300">Mensaje</label>
                <textarea rows="4" class="mt-2 w-full rounded-md border border-slate-800 bg-slate-950/70 p-3 text-sm text-slate-100" placeholder="Escribe tu mensaje"></textarea>
            </div>
            <button type="button" class="rounded-md bg-blue-600 py-3 text-sm font-bold uppercase tracking-wide text-white hover:bg-blue-500">
                Enviar (demo)
            </button>
        </form>
    </div>
</div>
@endsection