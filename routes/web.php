<?php

use App\Http\Controllers\Auth\SteamController;
use App\Http\Controllers\Lobby\LobbyController;
use App\Http\Controllers\Store\StoreController;
use App\Models\Lobby;
use App\Models\Server;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('welcome');

Route::get('/login', fn () => redirect()->route('login.steam'))->name('login');
Route::get('/login/steam', [SteamController::class, 'redirectToSteam'])->name('login.steam');
Route::get('/login/steam/callback', [SteamController::class, 'handleSteamCallback'])->name('login.steam.callback');

Route::get('/contact', function () {
    return view('contact');
})->name('contact');

Route::middleware('auth')->group(function () {
    Route::get('/home', function () {
        $user = request()->user();

        $activeLobbies = $user->lobbies()
            ->whereIn('status', ['waiting', 'live'])
            ->get();

        foreach ($activeLobbies as $lobby) {
            $lobby->users()->detach($user->id);

            $remainingPlayers = $lobby->users()->count();

            if ($remainingPlayers < $lobby->required_players) {
                $lobby->update([
                    'status' => 'waiting',
                    'started_at' => null,
                ]);
            }

            $lobby->server()->update([
                'current_players' => $remainingPlayers,
            ]);
        }

        // Keep counters in sync for all servers shown in home.
        $servers = Server::all();

        foreach ($servers as $server) {
            $playersInActiveLobby = Lobby::query()
                ->where('server_id', $server->id)
                ->whereIn('status', ['waiting', 'live'])
                ->withCount('users')
                ->get()
                ->max('users_count') ?? 0;

            if ($server->current_players !== $playersInActiveLobby) {
                $server->update([
                    'current_players' => $playersInActiveLobby,
                ]);
            }
        }

        $servers = Server::all()->map(function ($server) {
            $server->runtime_status = $server->isOnline() ? 'online' : 'offline';
            return $server;
        });

        return view('home', [
            'servers' => $servers,
        ]);
    })->name('home');

    Route::get('/profile', function () {
        return view('profile');
    })->name('profile');

    Route::get('/inventory', function () {
        return view('inventory');
    })->name('inventory');

    Route::get('/store', [StoreController::class, 'index'])->name('store');
    Route::get('/store/skins', [StoreController::class, 'skins'])->name('store.skins');

    Route::get('/lobby/{server}', [LobbyController::class, 'show'])->name('lobby.show');
    Route::get('/lobby/{server}/status', [LobbyController::class, 'status'])->name('lobby.status');
    Route::post('/logout', [SteamController::class, 'logout'])->name('logout');
});