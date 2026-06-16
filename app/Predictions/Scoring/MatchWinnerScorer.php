<?php

namespace App\Predictions\Scoring;

class MatchWinnerScorer implements Scorer
{
    public function score(array $value, array $settlement, int $basePoints): int
    {
        $selected = $value['selected'] ?? null;
        $winner = $settlement['winner'] ?? null;

        if ($selected === null || $winner === null) {
            return 0;
        }

        return $selected === $winner ? $basePoints : 0;
    }
}
