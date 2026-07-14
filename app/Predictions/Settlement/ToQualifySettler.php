<?php

namespace App\Predictions\Settlement;

use App\Models\Fixture;

class ToQualifySettler implements Settler
{
    public function settle(Fixture $fixture): ?array
    {
        if ($fixture->winner_team_id === null) {
            return null;
        }

        if ($fixture->winner_team_id === $fixture->home_team_id) {
            return ['selected' => 'home'];
        }

        if ($fixture->winner_team_id === $fixture->away_team_id) {
            return ['selected' => 'away'];
        }

        return null;
    }
}
