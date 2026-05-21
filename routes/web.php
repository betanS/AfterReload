<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Auth\SteamController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\Lobby\LobbyController;
use App\Http\Controllers\RankingController;
use App\Http\Controllers\ServerController;
use Illuminate\Support\Facades\Route;

Route::get('/lang/{locale}', [LanguageController::class, 'switch'])->name('lang.switch');

Route::get('/', fn () => view('welcome', ['userCount' => \App\Models\User::count()]))->name('welcome');
Route::get('/login', fn () => redirect()->route('login.steam'))->name('login');

Route::get('/login/steam', [SteamController::class, 'redirectToSteam'])->name('login.steam');
Route::get('/login/steam/callback', [SteamController::class, 'handleSteamCallback'])->name('login.steam.callback');

Route::get('/contact', fn () => view('contact'))->name('contact');
Route::post('/contact', [App\Http\Controllers\ContactController::class, 'send'])->name('contact.send');

Route::get('/ranking', [RankingController::class, 'index'])->name('ranking');

Route::middleware('auth')->group(function () {

    Route::get('/banned', fn () => view('banned'))->name('banned');
    Route::get('/home', fn () => redirect()->route('welcome'))->name('home');

    Route::get('/servers', [ServerController::class, 'index'])->name('servers.index');
    Route::get('/servers/data', [ServerController::class, 'data'])->name('servers.data');

    Route::get('/profile', fn () => view('profile'))->name('profile');
    Route::get('/inventory', function () {
        if (! auth()->user()->canAccessInventory()) {
            abort(403);
        }
        return view('inventory');
    })->name('inventory');
    Route::get('/store', fn () => view('store'))->name('store');

    Route::get('/lobby/{server}', [LobbyController::class, 'show'])->name('lobby.show');
    Route::get('/lobby/{server}/status', [LobbyController::class, 'status'])->name('lobby.status');
    Route::post('/lobby/{server}/leave', [LobbyController::class, 'leave'])->name('lobby.leave');
    Route::post('/lobby/{server}/team', [LobbyController::class, 'setTeam'])->name('lobby.team');
    Route::post('/lobby/{server}/ready', [LobbyController::class, 'toggleReady'])->name('lobby.ready');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [AdminController::class, 'index'])->name('index');
        Route::post('/servers', [AdminController::class, 'storeServer'])->name('servers.store');
        Route::post('/servers/clear-lobbies', [AdminController::class, 'clearLobbies'])->name('servers.clear-lobbies');
        Route::put('/servers/{server}', [AdminController::class, 'updateServer'])->name('servers.update');
        Route::delete('/servers/{server}', [AdminController::class, 'deleteServer'])->name('servers.delete');
        Route::post('/users/{user}/role', [AdminController::class, 'updateRole'])->name('users.role');
        Route::post('/users/{user}/stats', [AdminController::class, 'updateStats'])->name('users.stats');
        Route::post('/users/{user}/ban', [AdminController::class, 'toggleBan'])->name('users.ban');
        Route::post('/users/{user}/unban', [AdminController::class, 'unban'])->name('users.unban');
    });

    Route::delete('/account', [App\Http\Controllers\AccountController::class, 'destroy'])->name('account.delete');
    Route::post('/logout', [SteamController::class, 'logout'])->name('logout');
});
