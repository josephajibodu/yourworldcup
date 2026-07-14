<?php

namespace App\Predictions\Scoring;

class BooleanAnswerScorer implements Scorer
{
    public function score(array $value, array $settlement, int $basePoints): int
    {
        if (! array_key_exists('answer', $value) || ! array_key_exists('answer', $settlement)) {
            return 0;
        }

        return $value['answer'] === $settlement['answer'] ? $basePoints : 0;
    }
}
