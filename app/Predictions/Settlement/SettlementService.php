<?php

namespace App\Predictions\Settlement;

use App\Cache\TournamentCache;
use App\Enums\FixtureStatus;
use App\Enums\MarketStatus;
use App\Jobs\ResolveBracketSlots;
use App\Models\Fixture;
use App\Models\FixtureMarket;
use App\Models\Prediction;
use App\Predictions\Scoring\ScoringService;
use Illuminate\Support\Facades\DB;

class SettlementService
{
    public function __construct(
        private SettlerRegistry $settlers,
        private ScoringService $scoring,
        private TournamentCache $cache,
    ) {}

    /**
     * Settle every still-open market on a fixture and score its predictions.
     *
     * A final fixture settles each market from its paired settler; a void
     * fixture voids its markets and zeroes their predictions. Returns the
     * number of predictions scored.
     */
    public function settleFixture(Fixture $fixture, bool $includeSettled = false): int
    {
        if ($fixture->status === FixtureStatus::Void) {
            $scored = $this->voidFixture($fixture);

            if ($scored > 0) {
                $this->cache->bump('leaderboard');
            }

            return $scored;
        }

        if ($fixture->status !== FixtureStatus::Final) {
            return 0;
        }

        $statuses = [MarketStatus::Open];

        if ($includeSettled) {
            $statuses[] = MarketStatus::Settled;
        }

        $markets = $fixture->markets()
            ->whereIn('status', $statuses)
            ->with(['market', 'predictions'])
            ->get();

        $scored = DB::transaction(function () use ($fixture, $markets): int {
            $count = 0;

            foreach ($markets as $market) {
                $count += $this->settleMarket($fixture, $market);
            }

            return $count;
        });

        ResolveBracketSlots::dispatch($fixture->id);

        if ($scored > 0) {
            $this->cache->bump('leaderboard');
        }

        return $scored;
    }

    private function settleMarket(Fixture $fixture, FixtureMarket $market): int
    {
        $settler = $this->settlers->resolve($market->market->scorer);

        if ($settler === null) {
            return 0;
        }

        $settlement = $settler->settle($fixture);

        if ($settlement === null) {
            return 0;
        }

        $market->update([
            'settlement_value' => $settlement,
            'status' => MarketStatus::Settled,
            'settled_at' => now(),
        ]);

        $scored = 0;

        foreach ($market->predictions as $prediction) {
            // set the updated relation on it instead of loading it from the database
            $prediction->setRelation('fixtureMarket', $market);

            $prediction->update([
                'points_awarded' => $this->scoring->score($prediction),
                'scored_at' => now(),
            ]);

            $scored++;
        }

        return $scored;
    }

    private function voidFixture(Fixture $fixture): int
    {
        return DB::transaction(function () use ($fixture): int {
            $markets = $fixture->markets()
                ->whereIn('status', [MarketStatus::Open, MarketStatus::Locked])
                ->get();

            $scored = 0;

            foreach ($markets as $market) {
                $market->update([
                    'status' => MarketStatus::Void,
                    'settled_at' => now(),
                ]);

                $scored += Prediction::query()
                    ->where('fixture_market_id', $market->id)
                    ->update(['points_awarded' => 0, 'scored_at' => now()]);
            }

            return $scored;
        });
    }
}
