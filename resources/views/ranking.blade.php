@extends('layouts.app')

@section('title', 'Ranking')

@section('content')
<div class="app-root min-h-screen bg-slate-950 text-slate-100 p-8">
    <div class="max-w-6xl mx-auto">
        <div class="mb-6">
            <h2 class="text-2xl font-black">Ranking</h2>
            <p class="text-sm text-slate-400">Top 50 jugadores por puntos de rango.</p>
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-800 bg-slate-900/80">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-800 text-slate-300 uppercase">
                    <tr>
                        <th class="p-4">#</th>
                        <th class="p-4">Jugador</th>
                        <th class="p-4">Rango</th>
                        <th class="p-4 text-right">Perfil</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($players as $index => $player)
                        <tr class="transition hover:bg-slate-800/60">
                            <td class="p-4 text-slate-400">{{ $index + 1 }}</td>
                            <td class="p-4">
                                <div class="flex items-center gap-3">
                                    <img src="{{ $player->avatar }}" alt="{{ $player->steam_nickname ?? $player->name }}" class="h-9 w-9 rounded-full border border-blue-500/60">
                                    <div>
                                        <p class="font-semibold">{{ $player->steam_nickname ?? $player->name }}</p>
                                        <p class="text-xs uppercase tracking-[0.2em] text-slate-500">{{ $player->role }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="p-4 font-semibold text-blue-200">{{ $player->rank_points }}</td>
                            <td class="p-4 text-right">
                                @if($player->steam_id)
                                    <a href="https://steamcommunity.com/profiles/{{ $player->steam_id }}" target="_blank" rel="noreferrer" class="inline-flex items-center justify-center rounded-md border border-slate-700 px-3 py-1 text-xs font-semibold text-slate-200 hover:border-slate-500">
                                        Steam
                                    </a>
                                @else
                                    <span class="text-xs text-slate-500">No disponible</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="p-6 text-center text-sm text-slate-400">Sin jugadores disponibles.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
