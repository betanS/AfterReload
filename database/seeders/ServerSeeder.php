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
        Server::updateOrCreate(
            ['name' => 'Matchmaking Europa #1'],
            [
                'ip' => '185.47.131.129',
                'port' => 27015,
                'max_players' => 10,
                'current_players' => 0,
            ]
        );

        Server::updateOrCreate(
            ['name' => 'Matchmaking Europa #2'],
            [
                'ip' => '203.0.113.77',
                'port' => 27015,
                'max_players' => 10,
                'current_players' => 0,
            ]
        );

        Server::updateOrCreate(
            ['name' => 'Matchmaking Europa #3'],
            [
                'ip' => '203.0.113.88',
                'port' => 27015,
                'max_players' => 10,
                'current_players' => 0,
            ]
        );

        Server::updateOrCreate(
            ['name' => 'Wingman Europa #1'],
            [
                'ip' => '185.47.131.129',
                'port' => 27016,
                'max_players' => 4,
                'current_players' => 0,
            ]
        );
    }
}
