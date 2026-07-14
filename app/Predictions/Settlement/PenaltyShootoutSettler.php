<?php

namespace App\Predictions\Settlement;

use App\Enums\ResultDuration;
use App\Models\Fixture;

class PenaltyShootoutSettler implements Settler
{
    public function settle(Fixture $fixture): ?array
    {
        if ($fixture->home_score === null || $fixture->away_score === null) {
            return null;
        }

        $wentToPenalties = $fixture->result_duration === ResultDuration::Penalties
            || ($fixture->penalties_home !== null && $fixture->penalties_away !== null);

        return ['answer' => $wentToPenalties];
    }
}
