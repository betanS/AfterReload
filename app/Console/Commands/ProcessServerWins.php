<?php

namespace App\Console\Commands;

use App\Models\MatchResult;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ProcessServerWins extends Command
{
    protected $signature = 'server:process-wins';

    protected $description = 'Process public CS logs and award +1 RP to registered players on the winning team';

    protected string $logPath = '/var/www/afterreload/csgoserver/serverfiles/csgo/logs';

    /**
     * Competitive public server wins at 16 rounds.
     */
    protected int $winningScore = 16;

    public function handle(): int
    {
        if (! File::exists($this->logPath)) {
            $this->error('Logs directory not found.');

            return self::FAILURE;
        }

        $processedMatches = 0;

        foreach (File::files($this->logPath) as $file) {
            $processedMatches += $this->processLog($file->getPathname());
        }

        $this->info("Processed {$processedMatches} public match result(s).");

        return self::SUCCESS;
    }

    protected function processLog(string $filePath): int
    {
        $content = File::get($filePath);
        $lines = preg_split("/\r\n|\n|\r/", $content) ?: [];

        $players = [];
        $processed = 0;

        foreach ($lines as $index => $line) {
            $playerState = $this->extractPlayerState($line);

            if ($playerState !== null) {
                $players[$playerState['steam64']] = [
                    'steam64' => $playerState['steam64'],
                    'name' => $playerState['name'],
                    'team' => $playerState['team'],
                ];
            }

            $result = $this->extractFinishedMatch($line);

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

            $winnerSteamIds = collect($players)
                ->filter(fn (array $player) => $player['team'] === $result['winner_team'])
                ->keys()
                ->values()
                ->all();

            $awardedUsers = User::query()
                ->whereIn('steam_id', $winnerSteamIds)
                ->get();

            foreach ($awardedUsers as $user) {
                $user->increment('rank_points', 1);
            }

            MatchResult::query()->create([
                'event_hash' => $eventHash,
                'event_name' => 'public_log_result',
                'match_id' => basename($filePath) . ':' . $index,
                'server_id' => null,
                'winner_team' => $result['winner_team'],
                'team1_score' => $result['ct_score'],
                'team2_score' => $result['t_score'],
                'payload' => [
                    'source' => 'public_log',
                    'file' => $filePath,
                    'line_number' => $index + 1,
                    'line' => $line,
                    'winner_team' => $result['winner_team'],
                    'ct_score' => $result['ct_score'],
                    't_score' => $result['t_score'],
                    'winner_steam_ids' => $winnerSteamIds,
                    'awarded_steam_ids' => $awardedUsers->pluck('steam_id')->values()->all(),
                ],
                'processed_at' => now(),
            ]);

            $processed++;

            $this->info(sprintf(
                'Awarded +1 RP to %d registered player(s) on %s from %s:%d.',
                $awardedUsers->count(),
                $result['winner_team'],
                basename($filePath),
                $index + 1
            ));
        }

        return $processed;
    }

    /**
     * @return array{name: string, steam64: string, team: string}|null
     */
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

    /**
     * @return array{winner_team: string, ct_score: int, t_score: int}|null
     */
    protected function extractFinishedMatch(string $line): ?array
    {
        if (! preg_match('/Team "(CT|TERRORIST)" triggered "SFUI_Notice_(?:CTs|Terrorists)_Win" \(CT "(\d+)"\) \(T "(\d+)"\)/', $line, $matches)) {
            return null;
        }

        $winnerTeam = $this->normalizeTeam($matches[1]);
        $ctScore = (int) $matches[2];
        $tScore = (int) $matches[3];

        if ($winnerTeam === null) {
            return null;
        }

        $winnerScore = $winnerTeam === 'CT' ? $ctScore : $tScore;
        $loserScore = $winnerTeam === 'CT' ? $tScore : $ctScore;

        if ($winnerScore < $this->winningScore || $winnerScore <= $loserScore) {
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
}
