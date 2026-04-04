@extends('layouts.app')

@section('title', 'Ranking')

@section('content')
<div class="app-root min-h-screen bg-[#121212] text-slate-100 p-8">
    <div class="max-w-6xl mx-auto">
        <div class="mb-8">
            <h2 class="text-3xl font-black uppercase tracking-tighter text-white italic">Ranking Global</h2>
            <p class="text-sm text-slate-500 uppercase font-bold tracking-widest mt-1">Top 50 competidores de la temporada</p>
        </div>

        <div class="overflow-hidden rounded-sm border border-[#222222] bg-[#1b1b1b] shadow-2xl">
            <table class="w-full text-left text-sm">
                <thead class="bg-[#222222] text-slate-400 uppercase font-bold tracking-widest text-[10px] border-b border-[#333333]">
                    <tr>
                        <th class="p-4">Posición</th>
                        <th class="p-4">Jugador</th>
                        <th class="p-4">Elo Rating</th>
                        <th class="p-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#222222]">
                    @forelse($players as $index => $player)
                        <tr class="transition hover:bg-[#222222]/40 group">
                            <td class="p-4 text-slate-500 font-bold italic text-lg">
                                @if($index < 3)
                                    <span class="text-[#ff5500]">#{{ $index + 1 }}</span>
                                @else
                                    #{{ $index + 1 }}
                                @endif
                            </td>
                            <td class="p-4">
                                <div class="flex items-center gap-4">
                                    <img src="{{ $player->avatar }}" alt="{{ $player->steam_nickname ?? $player->name }}" class="h-10 w-10 rounded-sm border border-[#222222] group-hover:border-[#ff5500]/40 transition">
                                    <div>
                                        <p class="font-bold text-white text-base">{{ $player->steam_nickname ?? $player->name }}</p>
                                        <p class="text-[10px] uppercase tracking-[0.2em] text-slate-500 font-bold">{{ $player->role }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="p-4">
                                <span class="font-black text-xl text-white italic tracking-tighter">{{ $player->rank_points }}</span>
                                <span class="text-[10px] text-[#ff5500] font-bold uppercase ml-1">RP</span>
                            </td>
                            <td class="p-4 text-right">
                                @if($player->steam_id)
                                    <a href="https://steamcommunity.com/profiles/{{ $player->steam_id }}" target="_blank" rel="noreferrer" class="inline-flex items-center justify-center rounded-sm border border-[#222222] bg-[#121212] px-4 py-2 text-[10px] font-bold uppercase tracking-widest text-slate-300 hover:border-[#ff5500] hover:text-white transition">
                                        Perfil Steam
                                    </a>
                                @else
                                    <span class="text-[10px] text-slate-600 uppercase font-bold">Privado</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="p-12 text-center text-sm text-slate-500 uppercase font-bold tracking-widest">Sin registros competitivos</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
