<?php

namespace App\Predictions\Scoring;

class SelectedOptionScorer implements Scorer
{
    public function score(array $value, array $settlement, int $basePoints): int
    {
        $selected = $value['selected'] ?? null;
        $outcome = $settlement['selected'] ?? null;

        if ($selected === null || $outcome === null) {
            return 0;
        }

        return $selected === $outcome ? $basePoints : 0;
    }
}
