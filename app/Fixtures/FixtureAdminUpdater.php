<?php

namespace App\Fixtures;

use App\Cache\TournamentCache;
use App\Enums\FixtureStatus;
use App\Models\Fixture;
use App\Predictions\Settlement\SettlementService;

class FixtureAdminUpdater
{
    public function __construct(
        private FixtureResultRecorder $recorder,
        private SettlementService $settlement,
        private TournamentCache $cache,
    ) {}

    public function update(
        Fixture $fixture,
        FixtureStatus $status,
        ?FixtureResult $result,
        bool $settle,
    ): int {
        $wasFinal = $fixture->status === FixtureStatus::Final;
        $resultChanged = $wasFinal
            && $result !== null
            && ! $result->matchesFixture($fixture);

        match ($status) {
            FixtureStatus::Final => $this->recorder->record($fixture, $result ?? new FixtureResult(0, 0)),
            FixtureStatus::Live => $this->markLive($fixture),
            FixtureStatus::Void => $this->markVoid($fixture),
            FixtureStatus::Scheduled => $this->markScheduled($fixture),
        };

        $fixture->refresh();

        if (! $settle) {
            return 0;
        }

        return $this->settlement->settleFixture($fixture, includeSettled: $resultChanged);
    }

    private function markLive(Fixture $fixture): void
    {
        if (in_array($fixture->status, [FixtureStatus::Final, FixtureStatus::Void], true)) {
            return;
        }

        $fixture->update(['status' => FixtureStatus::Live]);
        $this->bumpCaches();
    }

    private function markVoid(Fixture $fixture): void
    {
        $fixture->update([
            'status' => FixtureStatus::Void,
            'home_score' => null,
            'away_score' => null,
            'extra_time_home' => null,
            'extra_time_away' => null,
            'penalties_home' => null,
            'penalties_away' => null,
            'result_duration' => null,
            'winner_team_id' => null,
        ]);
        $this->bumpCaches();
    }

    private function markScheduled(Fixture $fixture): void
    {
        $fixture->update([
            'status' => FixtureStatus::Scheduled,
            'home_score' => null,
            'away_score' => null,
            'extra_time_home' => null,
            'extra_time_away' => null,
            'penalties_home' => null,
            'penalties_away' => null,
            'result_duration' => null,
            'winner_team_id' => null,
        ]);
        $this->bumpCaches();
    }

    private function bumpCaches(): void
    {
        $this->cache->bump('bracket');
        $this->cache->bump('predict');
    }
}
