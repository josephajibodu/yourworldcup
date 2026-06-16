<?php

namespace App\Predictions\Settlement;

use App\Enums\FixtureStatus;
use App\Enums\MarketStatus;
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
    ) {}

    /**
     * Settle every still-open market on a fixture and score its predictions.
     *
     * A final fixture settles each market from its paired settler; a void
     * fixture voids its markets and zeroes their predictions. Returns the
     * number of predictions scored.
     */
    public function settleFixture(Fixture $fixture): int
    {
        if ($fixture->status === FixtureStatus::Void) {
            return $this->voidFixture($fixture);
        }

        if ($fixture->status !== FixtureStatus::Final) {
            return 0;
        }

        $markets = $fixture->markets()
            ->where('status', MarketStatus::Open)
            ->with(['market', 'predictions'])
            ->get();

        return DB::transaction(function () use ($fixture, $markets): int {
            $scored = 0;

            foreach ($markets as $market) {
                $scored += $this->settleMarket($fixture, $market);
            }

            return $scored;
        });
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
