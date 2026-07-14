<?php

namespace App\Predictions\Settlement;

use App\Models\Fixture;

class HomeCleanSheetSettler implements Settler
{
    public function settle(Fixture $fixture): ?array
    {
        if ($fixture->home_score === null || $fixture->away_score === null) {
            return null;
        }

        return ['answer' => $fixture->away_score === 0];
    }
}
