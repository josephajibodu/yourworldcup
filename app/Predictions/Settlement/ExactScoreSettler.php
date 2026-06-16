<?php

namespace App\Predictions\Settlement;

use App\Models\Fixture;

class ExactScoreSettler implements Settler
{
    public function settle(Fixture $fixture): ?array
    {
        if ($fixture->home_score === null || $fixture->away_score === null) {
            return null;
        }

        return [
            'home' => $fixture->home_score,
            'away' => $fixture->away_score,
        ];
    }
}
