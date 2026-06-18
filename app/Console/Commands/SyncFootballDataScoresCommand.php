<?php

namespace App\Console\Commands;

use App\FootballData\FootballDataScoreSyncService;
use Illuminate\Console\Command;
use RuntimeException;

class SyncFootballDataScoresCommand extends Command
{
    protected $signature = 'football-data:sync-scores
                            {--no-settle : Record scores without settling predictions}';

    protected $description = 'Fetch recent football-data.org matches, mark live fixtures, and settle finished ones';

    public function handle(): int
    {
        try {
            $result = FootballDataScoreSyncService::fromConfig()->syncRecent(settle: ! $this->option('no-settle'));
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Fetched %d match(es); updated %d, skipped %d, unlinked %d; scored %d prediction(s).',
            $result->fetched,
            $result->updated,
            $result->skipped,
            $result->unlinked,
            $result->settledPredictions,
        ));

        return self::SUCCESS;
    }
}
