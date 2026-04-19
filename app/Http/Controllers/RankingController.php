<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\View\View;

class RankingController extends Controller
{
    /**
     * Muestra el ranking de los mejores 50 jugadores.
     */
    public function index(): View
    {
        $players = User::whereNotNull('steam_id')
            ->orderByDesc('rank_points')
            ->take(50)
            ->get();

        return view('ranking', ['players' => $players]);
    }
}
