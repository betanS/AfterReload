<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $steamId = trim((string) env('ADMIN_STEAM_ID'));

        if ($steamId === '') {
            $this->command?->warn('ADMIN_STEAM_ID not set. Skipping admin seeder.');
            return;
        }

        $user = User::query()->where('steam_id', $steamId)->first();

        if (! $user) {
            $this->command?->warn('No user found with ADMIN_STEAM_ID.');
            return;
        }

        $user->update(['role' => 'admin']);
        $this->command?->info('Admin role assigned.');
    }
}
