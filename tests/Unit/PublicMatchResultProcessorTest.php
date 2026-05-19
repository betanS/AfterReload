<?php

namespace Tests\Unit;

use App\Services\PublicMatchResultProcessor;
use PHPUnit\Framework\TestCase;

class PublicMatchResultProcessorTest extends TestCase
{
    public function test_extract_finished_match_supports_bomb_defused_results_for_public_5v5(): void
    {
        $processor = new class extends PublicMatchResultProcessor
        {
            public function parseLine(string $line, int $winningScore = 16): ?array
            {
                return $this->extractFinishedMatch($line, $winningScore);
            }
        };

        $result = $processor->parseLine(
            'L 05/17/2026 - 11:58:03: Team "CT" triggered "SFUI_Notice_Bomb_Defused" (CT "16") (T "1")'
        );

        $this->assertSame([
            'winner_team' => 'CT',
            'ct_score' => 16,
            't_score' => 1,
        ], $result);
    }

    public function test_extract_finished_match_ignores_non_terminal_round_wins(): void
    {
        $processor = new class extends PublicMatchResultProcessor
        {
            public function parseLine(string $line, int $winningScore = 16): ?array
            {
                return $this->extractFinishedMatch($line, $winningScore);
            }
        };

        $result = $processor->parseLine(
            'L 05/17/2026 - 11:56:48: Team "CT" triggered "SFUI_Notice_CTs_Win" (CT "15") (T "1")'
        );

        $this->assertNull($result);
    }

    public function test_extract_finished_match_supports_wingman_ct_win_at_nine_rounds(): void
    {
        $processor = new class extends PublicMatchResultProcessor
        {
            public function parseLine(string $line, int $winningScore = 9): ?array
            {
                return $this->extractFinishedMatch($line, $winningScore);
            }
        };

        $result = $processor->parseLine(
            'L 05/17/2026 - 12:10:10: Team "CT" triggered "SFUI_Notice_Bomb_Defused" (CT "9") (T "3")'
        );

        $this->assertSame([
            'winner_team' => 'CT',
            'ct_score' => 9,
            't_score' => 3,
        ], $result);
    }

    public function test_extract_finished_match_supports_wingman_t_win_at_nine_rounds(): void
    {
        $processor = new class extends PublicMatchResultProcessor
        {
            public function parseLine(string $line, int $winningScore = 9): ?array
            {
                return $this->extractFinishedMatch($line, $winningScore);
            }
        };

        $result = $processor->parseLine(
            'L 05/17/2026 - 12:10:10: Team "TERRORIST" triggered "SFUI_Notice_Target_Bombed" (CT "7") (T "9")'
        );

        $this->assertSame([
            'winner_team' => 'TERRORIST',
            'ct_score' => 7,
            't_score' => 9,
        ], $result);
    }

    public function test_extract_finished_match_ignores_wingman_non_terminal_loss_lines(): void
    {
        $processor = new class extends PublicMatchResultProcessor
        {
            public function parseLine(string $line, int $winningScore = 9): ?array
            {
                return $this->extractFinishedMatch($line, $winningScore);
            }
        };

        $result = $processor->parseLine(
            'L 05/17/2026 - 12:08:10: Team "TERRORIST" triggered "SFUI_Notice_Target_Bombed" (CT "8") (T "8")'
        );

        $this->assertNull($result);
    }

    public function test_resolve_winning_score_uses_wingman_threshold_for_27016_logs(): void
    {
        $processor = new class extends PublicMatchResultProcessor
        {
            public function winningScoreForPath(string $filePath): int
            {
                return $this->resolveWinningScore(null, $filePath);
            }
        };

        $this->assertSame(
            9,
            $processor->winningScoreForPath('/tmp/L000_000_000_000_27016_202605171210_000.log')
        );
    }
}
