<?php

namespace App\Predictions\Scoring;

class ExactScoreScorer implements Scorer
{
    public function score(array $value, array $settlement, int $basePoints): int
    {
        if (! isset($value['home'], $value['away'], $settlement['home'], $settlement['away'])) {
            return 0;
        }

        $correct = (int) $value['home'] === (int) $settlement['home']
            && (int) $value['away'] === (int) $settlement['away'];

        return $correct ? $basePoints : 0;
    }
}
