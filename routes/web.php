<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Auth\SteamController;
use App\Http\Controllers\Lobby\LobbyController;
use App\Http\Controllers\Store\StoreController;
use App\Http\Controllers\ServerController;
use App\Http\Controllers\RankingController;
use App\Http\Controllers\LanguageController;
use Illuminate\Support\Facades\Route;

// Cambio de idioma
Route::get('/lang/{locale}', [LanguageController::class, 'switch'])->name('lang.switch');

// Bienvenida e inicio
Route::get('/', fn() => view('welcome'))->name('welcome');
Route::get('/login', fn() => redirect()->route('login.steam'))->name('login');

// Autenticación Steam
Route::get('/login/steam', [SteamController::class, 'redirectToSteam'])->name('login.steam');
Route::get('/login/steam/callback', [SteamController::class, 'handleSteamCallback'])->name('login.steam.callback');

// Soporte
Route::get('/contact', fn() => view('contact'))->name('contact');

// Ranking (Público)
Route::get('/ranking', [RankingController::class, 'index'])->name('ranking');

// Rutas protegidas por autenticación
Route::middleware('auth')->group(function () {
    
    // Usuario baneado
    Route::get('/banned', fn() => view('banned'))->name('banned');
    Route::get('/home', fn() => redirect()->route('welcome'))->name('home');

    // Servidores (Requiere Login)
    Route::get('/servers', [ServerController::class, 'index'])->name('servers.index');
    Route::get('/servers/data', [ServerController::class, 'data'])->name('servers.data');

    // Perfil e Inventario
    Route::get('/profile', fn() => view('profile'))->name('profile');
    Route::get('/inventory', fn() => view('inventory'))->name('inventory');

    // Tienda
    Route::get('/store', [StoreController::class, 'index'])->name('store');
    Route::get('/store/skins', [StoreController::class, 'skins'])->name('store.skins');

    // Matchmaking / Lobbies
    Route::get('/lobby/{server}', [LobbyController::class, 'show'])->name('lobby.show');
    Route::get('/lobby/{server}/status', [LobbyController::class, 'status'])->name('lobby.status');
    Route::post('/lobby/{server}/heartbeat', [LobbyController::class, 'heartbeat'])->name('lobby.heartbeat');
    Route::post('/lobby/{server}/leave', [LobbyController::class, 'leave'])->name('lobby.leave');
    Route::post('/lobby/{server}/team', [LobbyController::class, 'setTeam'])->name('lobby.team');
    Route::post('/lobby/{server}/ready', [LobbyController::class, 'toggleReady'])->name('lobby.ready');

    // Admin
    Route::prefix('admin')->name('admin.')->group(function() {
        Route::get('/', [AdminController::class, 'index'])->name('index');
        Route::post('/servers', [AdminController::class, 'storeServer'])->name('servers.store');
        Route::post('/servers/{server}', [AdminController::class, 'updateServer'])->name('servers.update');
        Route::post('/servers/{server}/sync', [AdminController::class, 'syncServer'])->name('servers.sync');
        Route::post('/servers/{server}/power', [AdminController::class, 'powerServer'])->name('servers.power');
        Route::post('/users/{user}/role', [AdminController::class, 'updateRole'])->name('users.role');
        Route::post('/users/{user}/ban', [AdminController::class, 'toggleBan'])->name('users.ban');
        Route::post('/users/{user}/unban', [AdminController::class, 'unban'])->name('users.unban');
    });

    Route::post('/logout', [SteamController::class, 'logout'])->name('logout');
});
