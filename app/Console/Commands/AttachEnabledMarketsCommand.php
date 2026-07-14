<?php

namespace App\Console\Commands;

use App\Cache\TournamentCache;
use App\Models\FixtureMarket;
use App\Predictions\Importing\WorldCupImporter;
use Database\Seeders\PredictionMarketSeeder;
use Illuminate\Console\Command;

class AttachEnabledMarketsCommand extends Command
{
    protected $signature = 'markets:attach-enabled
                            {--no-seed : Skip seeding prediction market definitions}';

    protected $description = 'Seed enabled prediction markets and attach them to fixtures without re-importing tournament data';

    public function handle(TournamentCache $cache): int
    {
        if (! $this->option('no-seed')) {
            $this->call('db:seed', [
                '--class' => PredictionMarketSeeder::class,
                '--force' => true,
            ]);
        }

        $before = FixtureMarket::count();

        WorldCupImporter::fromDefaultPath()->attachEnabledMarkets();

        $cache->bump('predict');

        $this->info(sprintf(
            'Attached enabled markets to all fixtures (%d -> %d fixture markets).',
            $before,
            FixtureMarket::count(),
        ));
        $this->line('Fixtures, scores, and bracket slots were not modified.');

        return self::SUCCESS;
    }
}
