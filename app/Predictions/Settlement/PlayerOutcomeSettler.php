<?php

namespace App\Predictions\Settlement;

use App\Models\Fixture;

class PlayerOutcomeSettler implements Settler
{
    public function __construct(private readonly string $marketKey) {}

    public function settle(Fixture $fixture): ?array
    {
        $outcomes = $fixture->player_outcomes;

        if (! is_array($outcomes) || ! array_key_exists($this->marketKey, $outcomes)) {
            return null;
        }

        return ['answer' => (bool) $outcomes[$this->marketKey]];
    }
}
