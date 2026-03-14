@extends('layouts.app')

@section('title', 'Home')

@section('content')
<div class="app-root min-h-screen bg-slate-950 text-slate-100 p-8">
    <div class="max-w-6xl mx-auto">
        @if (session('server_error'))
            <div class="mb-6 rounded-lg border border-red-400/40 bg-red-500/10 px-4 py-3 text-sm text-red-200">
                {{ session('server_error') }}
            </div>
        @endif

        <h3 class="text-2xl font-black uppercase tracking-tight mb-4 text-slate-100">Servidores Disponibles</h3>

        <div class="overflow-hidden rounded-xl border border-slate-800 bg-slate-900/80">
            <table class="w-full text-left">
                <thead class="bg-slate-800 text-slate-300 text-sm uppercase">
                    <tr>
                        <th class="p-4">Servidor</th>
                        <th class="p-4">Estado</th>
                        <th class="p-4">Jugadores</th>
                        <th class="p-4 text-right">Accion</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @foreach($servers as $server)
                    <tr class="transition hover:bg-slate-800/60">
                        <td class="p-4 font-semibold">{{ $server->name }}</td>
                        <td class="p-4">
                            <span class="px-2 py-1 rounded text-xs {{ $server->runtime_status === 'online' ? 'bg-blue-500/20 text-blue-300' : 'bg-slate-700 text-slate-300' }}">
                                {{ strtoupper($server->runtime_status) }}
                            </span>
                        </td>
                        <td class="p-4 text-slate-400">{{ $server->current_players }} / {{ $server->max_players }}</td>
                        <td class="p-4 text-right">
                            @if ($server->runtime_status === 'online')
                                <a href="{{ route('lobby.show', $server) }}" class="inline-block bg-blue-600 hover:bg-blue-500 text-white font-bold py-2 px-6 rounded-md transition">
                                    UNIRSE AL LOBBY
                                </a>
                            @else
                                <span class="inline-block bg-slate-800 text-slate-400 font-bold py-2 px-6 rounded-md border border-slate-700 cursor-not-allowed">
                                    OFFLINE
                                </span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection