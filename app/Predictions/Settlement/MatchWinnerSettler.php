<?php

namespace App\Predictions\Settlement;

use App\Models\Fixture;

class MatchWinnerSettler implements Settler
{
    public function settle(Fixture $fixture): ?array
    {
        if ($fixture->home_score === null || $fixture->away_score === null) {
            return null;
        }

        $winner = match (true) {
            $fixture->home_score > $fixture->away_score => 'home',
            $fixture->home_score < $fixture->away_score => 'away',
            default => 'draw',
        };

        return ['winner' => $winner];
    }
}
