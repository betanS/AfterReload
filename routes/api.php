<?php

use App\Http\Controllers\Api\Get5Controller;
use Illuminate\Support\Facades\Route;

Route::post('/get5/events', [Get5Controller::class, 'events'])->name('api.get5.events');
Route::get('/get5/match/{lobby}', [Get5Controller::class, 'match'])->name('api.get5.match');
