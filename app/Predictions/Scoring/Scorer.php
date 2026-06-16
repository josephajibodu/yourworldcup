<?php

namespace App\Predictions\Scoring;

interface Scorer
{
    /**
     * Award points for one prediction against its settled outcome.
     *
     * Pure and stateless: it knows nothing about banker doubling, users, or
     * leaderboards — that lives in ScoringService. A new market only needs a
     * new implementation of this contract.
     *
     * @param  array<string, mixed>  $value  The user's submitted prediction value.
     * @param  array<string, mixed>  $settlement  The market's settled outcome.
     * @param  int  $basePoints  Points this market is worth on this fixture.
     */
    public function score(array $value, array $settlement, int $basePoints): int;
}
