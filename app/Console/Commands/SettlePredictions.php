<?php

namespace App\Console\Commands;

use App\Enums\FixtureStatus;
use App\Enums\MarketStatus;
use App\Models\Fixture;
use App\Predictions\Settlement\SettlementService;
use Illuminate\Console\Command;

class SettlePredictions extends Command
{
    protected $signature = 'predictions:settle';

    protected $description = 'Settle markets on finished/void fixtures and score their predictions';

    public function handle(SettlementService $settlement): int
    {
        $fixtures = Fixture::query()
            ->whereIn('status', [FixtureStatus::Final, FixtureStatus::Void])
            ->whereHas('markets', fn ($query) => $query->whereIn('status', [MarketStatus::Open, MarketStatus::Locked]))
            ->get();

        $scored = 0;

        foreach ($fixtures as $fixture) {
            $scored += $settlement->settleFixture($fixture);
        }

        $this->info(sprintf(
            'Settled %d fixture(s), scored %d prediction(s).',
            $fixtures->count(),
            $scored,
        ));

        return self::SUCCESS;
    }
}
