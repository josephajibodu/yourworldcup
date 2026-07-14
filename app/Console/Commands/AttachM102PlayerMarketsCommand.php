<?php

namespace App\Console\Commands;

use App\Cache\TournamentCache;
use App\Fixtures\FixtureInspector;
use App\Predictions\Markets\M102PlayerMarkets;
use App\Predictions\Markets\PlayerMarketAttachmentService;

class AttachM102PlayerMarketsCommand extends AttachM101PlayerMarketsCommand
{
    protected $signature = 'markets:attach-m102-players
                            {--force : Attach even if teams do not match England and Argentina}';

    protected $description = 'Create M102 player prediction markets and attach them only to match 102';

    public function handle(
        FixtureInspector $inspector,
        PlayerMarketAttachmentService $attachment,
        TournamentCache $cache,
    ): int {
        return $this->attach(
            $inspector,
            $attachment,
            $cache,
            M102PlayerMarkets::MATCH_NUMBER,
            M102PlayerMarkets::EXPECTED_TEAMS,
            M102PlayerMarkets::definitions(),
        );
    }
}
