@extends('layouts.app')

@section('title', 'Cuenta bloqueada')

@section('content')
<div class="app-root min-h-screen bg-slate-950 text-slate-100 p-6 md:p-8">
    <div class="mx-auto max-w-3xl rounded-xl border border-slate-800 bg-slate-900/80 p-8 text-center">
        <p class="text-xs uppercase tracking-widest text-blue-400">Cuenta bloqueada</p>
        <h1 class="mt-3 text-3xl font-black">Acceso restringido</h1>
        <p class="mt-4 text-sm text-slate-300">
            Tu cuenta ha sido bloqueada temporalmente. Si crees que es un error, contacta con el staff.
        </p>
        <form method="POST" action="{{ route('logout') }}" class="mt-6">
            @csrf
            <button type="submit" class="rounded-md bg-blue-600 px-5 py-2 text-sm font-bold uppercase tracking-wide text-white hover:bg-blue-500">
                Cerrar sesion
            </button>
        </form>
    </div>
</div>
@endsection
