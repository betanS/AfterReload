@extends('layouts.app')

@section('title', 'Perfil')

@section('content')
<div class="max-w-4xl mx-auto p-8">
    <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-6">
        <h2 class="text-2xl font-black mb-4">Perfil</h2>
        <div class="flex items-center gap-4">
            <img src="{{ auth()->user()->avatar }}" class="h-16 w-16 rounded-full border border-blue-500/60" alt="Avatar Steam">
            <div>
                <p class="text-lg font-semibold">{{ auth()->user()->steam_nickname ?? auth()->user()->name }}</p>
                <p class="text-sm text-slate-400">Cuenta Steam conectada</p>
            </div>
        </div>
        <div class="mt-6 grid gap-3 sm:grid-cols-2">
            <div class="rounded-lg border border-slate-800 bg-slate-950/70 p-4">
                <p class="text-xs uppercase text-blue-300">Puntos de rango</p>
                <p class="text-xl font-bold">{{ auth()->user()->rank_points }}</p>
            </div>
            <div class="rounded-lg border border-slate-800 bg-slate-950/70 p-4">
                <p class="text-xs uppercase text-blue-300">Blue Credits</p>
                <p class="text-xl font-bold">{{ auth()->user()->blue_credits }}</p>
            </div>
        </div>
    </div>
</div>
@endsection