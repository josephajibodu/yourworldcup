<?php

namespace App\Predictions\Settlement;

use App\Models\Fixture;

class LastGoalSettler implements Settler
{
    public function settle(Fixture $fixture): ?array
    {
        if ($fixture->last_goal === null) {
            return null;
        }

        return ['selected' => $fixture->last_goal->value];
    }
}
