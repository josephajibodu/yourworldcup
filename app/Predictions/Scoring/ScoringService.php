<?php

namespace App\Predictions\Scoring;

use App\Models\Prediction;

class ScoringService
{
    public function __construct(protected ScorerRegistry $registry) {}

    /**
     * Compute points for a prediction against its settled market.
     *
     * Banker doubling is applied here — never inside a Scorer — so every
     * market type inherits it for free. Returns 0 if the market is unsettled.
     */
    public function score(Prediction $prediction): int
    {
        $fixtureMarket = $prediction->fixtureMarket;
        $settlement = $fixtureMarket->settlement_value;

        if ($settlement === null) {
            return 0;
        }

        $scorer = $this->registry->resolve($fixtureMarket->market->scorer);

        $points = $scorer->score(
            $prediction->value,
            $settlement,
            $fixtureMarket->effectiveBasePoints(),
        );

        return $prediction->is_banker ? $points * 2 : $points;
    }
}
