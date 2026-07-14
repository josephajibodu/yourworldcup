<?php

namespace App\Console\Commands;

use App\Cache\TournamentCache;
use Illuminate\Console\Command;

class RecoverTournamentCommand extends Command
{
    protected $signature = 'tournament:recover
                            {--no-sync : Skip football-data score backfill}
                            {--settle : Re-score open predictions after recovery}';

    protected $description = 'Restore scores and bracket teams after a bad import without re-importing fixtures from JSON';

    public function handle(TournamentCache $cache): int
    {
        $this->warn('Recovering tournament state from football-data and bracket resolution.');
        $this->line('This does not re-import database/data JSON or modify prediction markets.');

        if (! $this->option('no-sync')) {
            $exitCode = $this->call('football-data:sync-played');

            if ($exitCode !== self::SUCCESS) {
                $this->error('Score backfill failed. Ensure FOOTBALL_DATA_API_TOKEN is set, or pass --no-sync if restoring manually.');

                return self::FAILURE;
            }
        }

        $this->call('bracket:resolve', ['--all' => true]);

        foreach (TournamentCache::DOMAINS as $domain) {
            $cache->bump($domain);
        }

        if ($this->option('settle')) {
            $this->call('predictions:settle');
        }

        $this->newLine();
        $this->info('Tournament recovery complete.');
        $this->line('Verify key matches with: php artisan fixture:show 101');

        if (! $this->option('settle')) {
            $this->line('Run php artisan predictions:settle when you are ready to re-score predictions.');
        }

        return self::SUCCESS;
    }
}
