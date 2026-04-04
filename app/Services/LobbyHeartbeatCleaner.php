<?php

namespace App\Services;

use App\Models\Server;
use Illuminate\Support\Facades\Cache;

class LobbyHeartbeatCleaner
{
    /**
     * Remove lobby members that have not sent a heartbeat recently.
     */
    public function cleanup(Server $server, ?int $skipUserId = null, int $timeoutSeconds = 35): bool
    {
        $lobbies = $server->lobbies()
            ->whereIn('status', ['waiting', 'live'])
            ->get();

        $changed = false;

        foreach ($lobbies as $lobby) {
            $inactive = [];

            foreach ($lobby->users as $user) {
                if ($skipUserId !== null && $user->id === $skipUserId) {
                    continue;
                }

                $lastSeen = Cache::get("lobby:{$lobby->id}:user:{$user->id}:heartbeat");

                if (! $lastSeen || (now()->timestamp - $lastSeen) > $timeoutSeconds) {
                    $inactive[] = $user->id;
                }
            }

            if (empty($inactive)) {
                continue;
            }

            $lobby->users()->detach($inactive);
            $lobby->loadCount('users');

            if ($lobby->users_count < $lobby->required_players) {
                $lobby->update([
                    'status' => 'waiting',
                    'started_at' => null,
                ]);
            }

            $changed = true;
        }

        return $changed;
    }
}
