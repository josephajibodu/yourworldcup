<?php

namespace App\Predictions\Settlement;

use App\Models\Fixture;

interface Settler
{
    /**
     * Derive a market's settlement value from a finished fixture.
     *
     * Returns the settlement payload the paired Scorer consumes, or null when
     * the fixture does not yet carry the data this market needs (e.g. missing
     * score), in which case the market is left open for a later attempt.
     *
     * @return array<string, mixed>|null
     */
    public function settle(Fixture $fixture): ?array;
}
