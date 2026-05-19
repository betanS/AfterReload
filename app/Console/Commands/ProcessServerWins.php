<?php

namespace App\Console\Commands;

use App\Services\PublicMatchResultProcessor;
use Illuminate\Console\Command;

class ProcessServerWins extends Command
{
    protected $signature = 'server:process-wins {--force : Ignore throttling and run immediately}';

    protected $description = 'Process public CS logs and award points to registered players on the winning team';

    public function handle(PublicMatchResultProcessor $processor): int
    {
        $processedMatches = $processor->process((bool) $this->option('force'));

        $this->info("Processed {$processedMatches} public match result(s).");

        return self::SUCCESS;
    }
}
