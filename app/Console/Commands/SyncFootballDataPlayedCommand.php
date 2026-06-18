<?php

namespace App\Console\Commands;

use App\FootballData\FootballDataScoreSyncService;
use Illuminate\Console\Command;
use RuntimeException;

class SyncFootballDataPlayedCommand extends Command
{
    protected $signature = 'football-data:sync-played
                            {--settle : Settle predictions for updated fixtures}';

    protected $description = 'Fetch all finished football-data.org matches for the season and update local scores';

    public function handle(): int
    {
        try {
            $result = FootballDataScoreSyncService::fromConfig()->syncAllPlayed(settle: $this->option('settle'));
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
