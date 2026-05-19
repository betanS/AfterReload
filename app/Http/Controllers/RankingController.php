<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\View\View;

class RankingController extends Controller
{
    public function index(): View
    {
        $players = User::whereNotNull('steam_id')
            ->orderByDesc('points')
            ->take(50)
            ->get();

        return view('ranking', ['players' => $players]);
    }
}
