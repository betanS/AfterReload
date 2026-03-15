<?php

namespace Database\Seeders;

use App\Models\Server;
use Illuminate\Database\Seeder;

class ServerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Server::query()->delete();

        Server::create([
            'name' => 'Public Lobby #1',
            'ip' => '185.47.131.129',
            'port' => 27015,
            'type' => 'public',
            'max_players' => 10,
            'current_players' => 0,
        ]);

        Server::create([
            'name' => 'Public Lobby Wingman',
            'ip' => '185.47.131.129',
            'port' => 27016,
            'type' => 'public',
            'max_players' => 4,
            'current_players' => 0,
        ]);

        Server::create([
            'name' => 'AfterReload Matchmaking',
            'ip' => '185.47.131.129',
            'port' => 27017,
            'type' => 'mm',
            'max_players' => 10,
            'current_players' => 0,
        ]);
    }
}
