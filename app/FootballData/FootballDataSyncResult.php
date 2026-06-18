<?php

namespace App\FootballData;

class FootballDataSyncResult
{
    public function __construct(
        public int $fetched = 0,
        public int $updated = 0,
        public int $skipped = 0,
        public int $unlinked = 0,
        public int $settledPredictions = 0,
    ) {}
}
