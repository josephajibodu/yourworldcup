<?php

namespace App\Console\Commands;

use App\Cache\TournamentCache;
use App\Fixtures\FixtureInspector;
use App\Models\FixtureMarket;
use App\Models\PredictionMarket;
use App\Predictions\Markets\M101PlayerMarkets;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

class AttachM101PlayerMarketsCommand extends Command
{
    protected $signature = 'markets:attach-m101-players
                            {--force : Attach even if teams do not match France and Spain}';

    protected $description = 'Create M101 player prediction markets and attach them only to match 101';

    public function handle(FixtureInspector $inspector, TournamentCache $cache): int
    {
        try {
            $fixture = $inspector->findByMatchNumber(M101PlayerMarkets::MATCH_NUMBER);

            if (! $this->option('force')) {
                $inspector->assertTeams($fixture, M101PlayerMarkets::EXPECTED_TEAMS);
            }
        } catch (ValidationException $exception) {
            $this->error($exception->getMessage());
            $this->line('Use fixture:show 101 to verify the match, or pass --force to override.');

            return self::FAILURE;
        }

        $attached = 0;

        foreach (M101PlayerMarkets::definitions() as $definition) {
            $market = PredictionMarket::updateOrCreate(
                ['key' => $definition['key']],
                [
                    'name' => $definition['name'],
                    'description' => $definition['description'],
                    'input_type' => $definition['input_type'],
                    'options' => null,
                    'base_points' => $definition['base_points'],
                    'scorer' => $definition['key'],
                    'is_default' => false,
                    'is_enabled' => false,
                    'sort_order' => $definition['sort_order'],
                ],
            );

            FixtureMarket::firstOrCreate([
                'fixture_id' => $fixture->id,
                'prediction_market_id' => $market->id,
            ]);

            $attached++;
        }

        $cache->bump('predict');

        $summary = $inspector->summarize($fixture->fresh(['homeTeam', 'awayTeam', 'stadium', 'markets.market']));

        $this->info(sprintf(
            'Attached %d player markets to M%s (%s vs %s).',
            $attached,
            $summary['externalId'],
            $summary['homeTeam'] ?? 'TBD',
            $summary['awayTeam'] ?? 'TBD',
        ));

        return self::SUCCESS;
    }
}
