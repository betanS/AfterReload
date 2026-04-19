<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lobby;
use App\Models\LobbyMatch;
use App\Models\MatchResult;
use App\Models\Server;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class Get5Controller extends Controller
{
    public function events(Request $request): JsonResponse
    {
        $token = trim((string) env('GET5_WEBHOOK_TOKEN'));
        $authHeader = (string) $request->header('Authorization');
        $providedToken = str_starts_with($authHeader, 'Bearer ')
            ? substr($authHeader, 7)
            : (string) $request->header('X-Get5-Token');

        if ($token !== '' && ! hash_equals($token, $providedToken)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $payload = $request->json()->all();
        $eventHash = hash('sha256', json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        if (MatchResult::query()->where('event_hash', $eventHash)->exists()) {
            return response()->json(['status' => 'duplicate']);
        }

        $eventName = $payload['event'] ?? $payload['event_name'] ?? null;
        $matchId = $payload['matchid'] ?? $payload['match_id'] ?? null;
        $mapNumber = $payload['map_number'] ?? null;
        $serverId = $request->header('Get5-ServerId');

        $team1 = $payload['team1'] ?? null;
        $team2 = $payload['team2'] ?? null;
        $winner = $payload['winner'] ?? null;
        $team1Score = $payload['team1_score'] ?? $payload['team1']['score'] ?? null;
        $team2Score = $payload['team2_score'] ?? $payload['team2']['score'] ?? null;

        MatchResult::query()->create([
            'event_hash' => $eventHash,
            'event_name' => is_string($eventName) ? $eventName : null,
            'match_id' => is_string($matchId) ? $matchId : null,
            'map_number' => is_numeric($mapNumber) ? (int) $mapNumber : null,
            'server_id' => is_numeric($serverId) ? (int) $serverId : null,
            'winner_team' => is_string($winner) ? $winner : null,
            'team1_score' => is_numeric($team1Score) ? (int) $team1Score : null,
            'team2_score' => is_numeric($team2Score) ? (int) $team2Score : null,
            'payload' => $payload,
            'processed_at' => now(),
        ]);

        if (! in_array($eventName, ['series_result', 'map_result', 'series_end', 'map_end'], true)) {
            return response()->json(['status' => 'logged']);
        }

        if (! $winner || ! is_array($team1) || ! is_array($team2)) {
            return response()->json(['status' => 'no_winner']);
        }

        $team1Players = $this->extractPlayers($team1);
        $team2Players = $this->extractPlayers($team2);

        $winnerTeam = $winner === 'team1' ? $team1Players : ($winner === 'team2' ? $team2Players : []);
        $loserTeam = $winner === 'team1' ? $team2Players : ($winner === 'team2' ? $team1Players : []);

        // Only apply points on series end to avoid double points in multi-map matches
        if (in_array($eventName, ['series_result', 'series_end'], true)) {
            $server = Server::find($serverId);
            
            if ($server) {
                if ($server->type === 'mm') {
                    // Standard MM Rewards
                    $this->applyPoints($winnerTeam, 10);
                    $this->applyPoints($loserTeam, -5);
                } elseif ($server->type === 'public' || $server->type === 'csgo') {
                    // Public/Casual Rewards: +1 for winners, 0 for losers
                    // This applies to anyone Get5 reports as part of the team at the end
                    $this->applyPoints($winnerTeam, 1);
                    $this->applyPoints($loserTeam, 0);
                }
            }

            // Kick everyone from the lobby (only for MM lobbies, public ones are fluid)
            $lobby = Lobby::query()
                ->where('server_id', $serverId)
                ->where('status', 'live')
                ->latest('id')
                ->first();

            if ($lobby) {
                if ($server && $server->type === 'mm') {
                    $lobby->users()->detach(); // Remove everyone from MM lobby
                }
                
                $lobby->update(['status' => 'completed']);

                // Close the match record too
                LobbyMatch::query()
                    ->where('lobby_id', $lobby->id)
                    ->whereIn('status', ['pending', 'live'])
                    ->update(['status' => 'completed']);

                // Clear server player count
                Server::query()->where('id', $serverId)->update(['current_players' => 0]);

                // Re-enable skin commands
                $server = $lobby->server;
                if ($server) {
                    $rcon = app(\App\Services\RconClient::class);
                    $rconHost = (string) env('RCON_HOST', $server->ip);
                    $rconPort = (int) env('RCON_PORT', $server->port);
                    $rconPassword = (string) env('RCON_PASSWORD', '');

                    if ($rconPassword) {
                        $rcon->send($rconHost, $rconPort, $rconPassword, 'sm_rename_command sm_ws_disabled_mm sm_ws');
                        $rcon->send($rconHost, $rconPort, $rconPassword, 'sm_rename_command sm_gloves_disabled_mm sm_gloves');
                        $rcon->send($rconHost, $rconPort, $rconPassword, 'sm_rename_command sm_agents_disabled_mm sm_agents');
                        $rcon->send($rconHost, $rconPort, $rconPassword, 'sm_weapons_table_prefix ""');
                    }
                }
            }        }

        return response()->json(['status' => 'processed']);
    }

    public function match(Lobby $lobby, Request $request): JsonResponse
    {
        $token = trim((string) env('GET5_WEBHOOK_TOKEN'));
        $authHeader = (string) $request->header('Authorization');
        $providedToken = str_starts_with($authHeader, 'Bearer ')
            ? substr($authHeader, 7)
            : (string) $request->header('X-Get5-Token');

        if ($token !== '' && ! hash_equals($token, $providedToken)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        try {
            return response()->json($this->buildOrCreateLobbyMatch($lobby));
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function buildOrCreateLobbyMatch(Lobby $lobby): array
    {
        $server = $lobby->server;
        if (! $server) {
            throw new \RuntimeException('Server missing');
        }

        $existing = LobbyMatch::query()
            ->where('lobby_id', $lobby->id)
            ->whereIn('status', ['pending', 'live'])
            ->latest('id')
            ->first();

        if ($existing) {
            if ($existing->status === 'pending') {
                $existing->update(['status' => 'live']);
            }

            return (array) $existing->config;
        }

        $lobby->load('users');
        $users = $lobby->users->filter(fn ($user) => ! empty($user->steam_id));

        $ctPlayers = $users->filter(fn ($user) => $user->pivot?->team === 'ct');
        $tPlayers = $users->filter(fn ($user) => $user->pivot?->team === 't');

        if ($ctPlayers->isEmpty() || $tPlayers->isEmpty() || $ctPlayers->count() !== $tPlayers->count()) {
            throw new \RuntimeException('Los equipos deben estar balanceados antes de crear la partida.');
        }

        $matchId = sprintf('ar-%s-%s', $lobby->id, now()->format('YmdHis'));
        $map = env('GET5_DEFAULT_MAP', 'de_mirage');
        $playersPerTeam = max(1, $ctPlayers->count());
        $password = bin2hex(random_bytes(4));

        $config = [
            'matchid' => $matchId,
            'players_per_team' => $playersPerTeam,
            'min_players_to_ready' => $users->count(),
            'maplist' => [$map],
            'password' => $password,
            'cvars' => [
                'get5_auto_ready' => '1',
                'get5_move_to_spec_on_ready' => '1',
                'get5_skip_knife_round' => '1',
            ],
            'team1' => [
                'name' => 'AfterReload CT',
                'players' => $ctPlayers->mapWithKeys(fn ($user) => [$user->steam_id => $user->steam_nickname ?? $user->name])->all(),
            ],
            'team2' => [
                'name' => 'AfterReload T',
                'players' => $tPlayers->mapWithKeys(fn ($user) => [$user->steam_id => $user->steam_nickname ?? $user->name])->all(),
            ],
        ];

        LobbyMatch::query()->create([
            'lobby_id' => $lobby->id,
            'server_id' => $server->id,
            'match_id' => $matchId,
            'config' => $config,
            'status' => 'pending',
            'created_at' => now(),
        ]);

        return $config;
    }

    /**
     * @param  array<string, mixed>  $team
     * @return array<int, string>
     */
    private function extractPlayers(array $team): array
    {
        $players = $team['players'] ?? [];

        if (is_array($players)) {
            if (array_is_list($players)) {
                return collect($players)
                    ->map(fn ($player) => $player['steamid'] ?? $player['steam_id'] ?? null)
                    ->filter(fn ($id) => is_string($id) && $id !== '')
                    ->values()
                    ->all();
            }

            return collect(array_keys($players))
                ->filter(fn ($id) => is_string($id) && $id !== '')
                ->values()
                ->all();
        }

        return [];
    }

    /**
     * @param  array<int, string>  $steamIds
     */
    protected function applyPoints(array $steamIds, int $delta): void
    {
        if (empty($steamIds)) {
            return;
        }

        User::query()
            ->whereIn('steam_id', $steamIds)
            ->get()
            ->each(function (User $user) use ($delta) {
                $next = $user->rank_points + $delta;
                $user->update([
                    'rank_points' => max(0, $next),
                ]);

                // Sync with LevelsRanks on the server
                $this->syncLevelRanks($user->steam_id, max(0, $next));
            });
    }

    /**
     * Synchronize points with the LevelsRanks database.
     */
    private function syncLevelRanks(string $steamId, int $points): void
    {
        try {
            $conn = \Illuminate\Support\Facades\DB::connection('server');

            // SteamID in LevelRanks (lvl_base) is usually SteamID2 or SteamID3
            // but we'll try to match it. If the server uses SteamID64, we use that.
            // Most modern LR versions use SteamID64 or match whatever is provided.
            
            $exists = $conn->table('lvl_base')->where('steam', $steamId)->exists();

            if ($exists) {
                $conn->table('lvl_base')
                    ->where('steam', $steamId)
                    ->update(['value' => $points]);
            } else {
                // If they don't exist, we can't easily create them because LR 
                // requires other fields (name, etc.), but we'll try a basic insert 
                // if the table allows it.
                $conn->table('lvl_base')->insertOrIgnore([
                    'steam' => $steamId,
                    'value' => $points,
                    'name' => 'WebSync Player',
                    'lastconnect' => time(),
                ]);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("LevelRanks Sync Failed for {$steamId}: " . $e->getMessage());
        }
    }
}
