<?php

namespace App\Predictions\Settlement;

use App\Models\Fixture;

class HighestBookingSettler implements Settler
{
    public function settle(Fixture $fixture): ?array
    {
        if ($fixture->highest_booking === null) {
            return null;
        }

        return ['selected' => $fixture->highest_booking->value];
    }
}
