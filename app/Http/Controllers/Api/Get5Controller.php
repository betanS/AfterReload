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

        $this->applyPoints($winnerTeam, 10);
        $this->applyPoints($loserTeam, -8);

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

        $server = $lobby->server;
        if (! $server) {
            return response()->json(['message' => 'Server missing'], 404);
        }

        $existing = LobbyMatch::query()->where('lobby_id', $lobby->id)->first();
        if ($existing) {
            return response()->json($existing->config);
        }

        $lobby->load('users');
        $users = $lobby->users->filter(fn ($user) => ! empty($user->steam_id));

        $ctPlayers = $users->filter(fn ($user) => $user->pivot?->team === 'ct');
        $tPlayers = $users->filter(fn ($user) => $user->pivot?->team === 't');

        $matchId = sprintf('ar-%s-%s', $lobby->id, now()->format('YmdHis'));
        $map = env('GET5_DEFAULT_MAP', 'de_mirage');
        $playersPerTeam = max(1, intdiv($lobby->required_players ?: min($server->max_players, 10), 2));

        $config = [
            'matchid' => $matchId,
            'players_per_team' => $playersPerTeam,
            'min_players_to_ready' => 2,
            'maplist' => [$map],
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

        return response()->json($config);
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
    private function applyPoints(array $steamIds, int $delta): void
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
            });
    }
}
