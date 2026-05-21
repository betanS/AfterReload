<?php

namespace App\Services;

use App\Models\MatchResult;
use App\Models\Server;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class PublicMatchResultProcessor
{
    private const LOCK_KEY = 'public-match-result-processor:lock';

    private const LAST_RUN_KEY = 'public-match-result-processor:last-run-at';

    private const DEFAULT_WINNING_SCORE = 16;

    private const WINGMAN_WINNING_SCORE = 9;

    public function __construct(
        private readonly string $logPath = '/var/www/afterreload/csgoserver/serverfiles/csgo/logs',
        private readonly int $runIntervalSeconds = 15,
    ) {}

    public function process(bool $force = false): int
    {
        if (! File::exists($this->logPath)) {
            return 0;
        }

        if (! $force && ! $this->shouldRun()) {
            return 0;
        }

        if (! Cache::add(self::LOCK_KEY, now()->timestamp, 30)) {
            return 0;
        }

        try {
            Cache::forever(self::LAST_RUN_KEY, now()->timestamp);

            $processedMatches = 0;
            foreach (File::files($this->logPath) as $file) {
                $processedMatches += $this->processLog($file->getPathname());
            }

            return $processedMatches;
        } finally {
            Cache::forget(self::LOCK_KEY);
        }
    }

    protected function shouldRun(): bool
    {
        $lastRunAt = Cache::get(self::LAST_RUN_KEY);

        if (! is_numeric($lastRunAt)) {
            return true;
        }

        return (now()->timestamp - (int) $lastRunAt) >= $this->runIntervalSeconds;
    }

    protected function processLog(string $filePath): int
    {
        $content = File::get($filePath);
        $lines = preg_split("/\r\n|\n|\r/", $content) ?: [];
        $players = [];
        $processed = 0;
        $serverId = $this->resolveServerIdFromLogPath($filePath);
        $server = $serverId ? Server::query()->find($serverId) : null;
        $winningScore = $this->resolveWinningScore($server, $filePath);

        foreach ($lines as $index => $line) {
            $playerState = $this->extractPlayerState($line);

            if ($playerState !== null) {
                $players[$playerState['steam64']] = [
                    'steam64' => $playerState['steam64'],
                    'name' => $playerState['name'],
                    'team' => $playerState['team'],
                ];
            }

            $result = $this->extractFinishedMatch($line, $winningScore);

            if ($result === null) {
                continue;
            }

            $eventHash = hash('sha256', implode('|', [
                'public-log-result',
                $filePath,
                (string) $index,
                $result['winner_team'],
                (string) $result['ct_score'],
                (string) $result['t_score'],
            ]));

            if (MatchResult::query()->where('event_hash', $eventHash)->exists()) {
                continue;
            }

            $playerSteamIdsByTeam = collect($players)
                ->groupBy('team')
                ->map(fn ($teamPlayers) => $teamPlayers->pluck('steam64')->unique()->all());

            $winnerTeam = $result['winner_team'];
            $loserTeam = $winnerTeam === 'CT' ? 'TERRORIST' : 'CT';
            $winnerSteamIds = $playerSteamIdsByTeam->get($winnerTeam, []);
            $loserSteamIds = $playerSteamIdsByTeam->get($loserTeam, []);

            $lobbyUserSteamIds = $serverId
                ? \App\Models\Lobby::query()
                    ->where('server_id', $serverId)
                    ->whereIn('status', ['waiting', 'live'])
                    ->get()
                    ->flatMap(fn ($lobby) => $lobby->users()->pluck('steam_id'))
                    ->unique()
                    ->all()
                : [];

            $winnerSteamIds = array_intersect($winnerSteamIds, $lobbyUserSteamIds);
            $loserSteamIds  = array_intersect($loserSteamIds, $lobbyUserSteamIds);

            $winnerUsers = User::query()->whereIn('steam_id', $winnerSteamIds)->get();
            $loserUsers = User::query()->whereIn('steam_id', $loserSteamIds)->get();

            foreach ($winnerUsers as $user) {
                $user->points += 5;
                $user->credits += 2;
                $user->save();
            }

            foreach ($loserUsers as $user) {
                $user->points = max(0, $user->points - 4);
                $user->save();
            }

            MatchResult::query()->create([
                'event_hash' => $eventHash,
                'event_name' => 'public_log_result',
                'match_id' => basename($filePath).':'.$index,
                'server_id' => $serverId,
                'winner_team' => $winnerTeam,
                'team1_score' => $result['ct_score'],
                'team2_score' => $result['t_score'],
                'payload' => [
                    'source' => 'public_log',
                    'file' => $filePath,
                    'line_number' => $index + 1,
                    'line' => $line,
                    'winner_team' => $winnerTeam,
                    'ct_score' => $result['ct_score'],
                    't_score' => $result['t_score'],
                    'winner_steam_ids' => $winnerSteamIds,
                    'loser_steam_ids' => $loserSteamIds,
                    'awarded_winners' => $winnerUsers->pluck('steam_id')->all(),
                    'penalized_losers' => $loserUsers->pluck('steam_id')->all(),
                ],
                'processed_at' => now(),
            ]);

            $processed++;
        }

        return $processed;
    }

    protected function extractPlayerState(string $line): ?array
    {
        if (! preg_match('/"(.+?)<\d+><(STEAM_\d+:\d+:\d+)><([^>]*)>"/', $line, $matches)) {
            return null;
        }

        $team = $this->normalizeTeam($matches[3]);

        if ($team === null) {
            return null;
        }

        return [
            'name' => $matches[1],
            'steam64' => $this->convertSteamIdTo64($matches[2]),
            'team' => $team,
        ];
    }

    protected function extractFinishedMatch(string $line, int $winningScore = self::DEFAULT_WINNING_SCORE): ?array
    {
        if (! preg_match('/Team "(CT|TERRORIST)" triggered "(SFUI_Notice_(?:CTs|Terrorists)_Win|SFUI_Notice_Bomb_Defused|SFUI_Notice_Target_Bombed|SFUI_Notice_Hostages_Rescued|SFUI_Notice_Hostages_Not_Rescued|SFUI_Notice_Terrorists_Escaped|SFUI_Notice_CTs_PreventEscape|SFUI_Notice_Escaping_Terrorists_Neutralized)" \(CT "(\d+)"\) \(T "(\d+)"\)/', $line, $matches)) {
            return null;
        }

        $winnerTeam = $this->normalizeTeam($matches[1]);
        $ctScore = (int) $matches[3];
        $tScore = (int) $matches[4];

        if ($winnerTeam === null) {
            return null;
        }

        $winnerScore = $winnerTeam === 'CT' ? $ctScore : $tScore;
        $loserScore = $winnerTeam === 'CT' ? $tScore : $ctScore;

        if ($winnerScore < $winningScore || $winnerScore <= $loserScore) {
            return null;
        }

        return [
            'winner_team' => $winnerTeam,
            'ct_score' => $ctScore,
            't_score' => $tScore,
        ];
    }

    protected function normalizeTeam(string $team): ?string
    {
        return match ($team) {
            'CT' => 'CT',
            'TERRORIST' => 'TERRORIST',
            default => null,
        };
    }

    protected function convertSteamIdTo64(string $steamId): string
    {
        if (! preg_match('/^STEAM_[0-5]:([01]):(\d+)$/', $steamId, $matches)) {
            return $steamId;
        }

        $y = (int) $matches[1];
        $z = (int) $matches[2];

        return (string) (76561197960265728 + ($z * 2) + $y);
    }

    protected function resolveServerIdFromLogPath(string $filePath): ?int
    {
        if (! preg_match('/_(\d{5})_\d{12}_\d+\.log$/', basename($filePath), $matches)) {
            return null;
        }

        return Server::query()
            ->where('type', 'public')
            ->where('port', (int) $matches[1])
            ->value('id');
    }

    protected function resolveWinningScore(?Server $server, string $filePath): int
    {
        if ($server && $server->type === 'public' && (int) $server->max_players <= 4) {
            return self::WINGMAN_WINNING_SCORE;
        }

        if (preg_match('/_(27016)_\d{12}_\d+\.log$/', basename($filePath))) {
            return self::WINGMAN_WINNING_SCORE;
        }

        return self::DEFAULT_WINNING_SCORE;
    }
}
