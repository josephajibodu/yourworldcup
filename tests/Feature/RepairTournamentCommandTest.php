<?php

use App\Bracket\BracketResolverService;
use App\Enums\FixtureStage;
use App\Enums\FixtureStatus;
use App\Enums\ResultDuration;
use App\Models\BracketSlot;
use App\Models\Fixture;
use App\Models\Team;
use App\Predictions\Importing\WorldCupImporter;
use Database\Seeders\PredictionMarketSeeder;

beforeEach(function () {
    $this->seed(PredictionMarketSeeder::class);
    WorldCupImporter::fromDefaultPath()->import();
});

test('tournament repair lists steps when no flags are passed', function () {
    $this->artisan('tournament:repair')
        ->expectsOutputToContain('Available steps')
        ->expectsOutputToContain('--backfill')
        ->expectsOutputToContain('--propagate-r32')
        ->assertSuccessful();
});

test('tournament repair backfills winners from penalties and extra time', function () {
    $fixture = Fixture::query()->where('external_id', '74')->firstOrFail();

    $fixture->update([
        'status' => FixtureStatus::Final,
        'home_team_id' => Team::query()->where('group_code', 'A')->firstOrFail()->id,
        'away_team_id' => Team::query()->where('group_code', 'B')->firstOrFail()->id,
        'home_score' => 1,
        'away_score' => 1,
        'extra_time_home' => 0,
        'extra_time_away' => 0,
        'penalties_home' => 3,
        'penalties_away' => 4,
        'result_duration' => ResultDuration::Penalties,
        'winner_team_id' => null,
    ]);

    $this->artisan('tournament:repair --backfill')
        ->expectsOutputToContain('Backfilled 1 knockout winner(s)')
        ->assertSuccessful();

    $fixture->refresh();

    expect($fixture->winner_team_id)->toBe($fixture->away_team_id);
});

test('tournament repair backfill ignores group draws', function () {
    $groupDraw = Fixture::query()
        ->where('stage', FixtureStage::Group)
        ->firstOrFail();

    $groupDraw->update([
        'status' => FixtureStatus::Final,
        'home_score' => 1,
        'away_score' => 1,
        'winner_team_id' => null,
    ]);

    $this->artisan('tournament:repair --backfill')
        ->expectsOutputToContain('Group draws are left without a winner on purpose')
        ->doesntExpectOutputToContain('M'.$groupDraw->external_id)
        ->assertSuccessful();

    expect($groupDraw->fresh()->winner_team_id)->toBeNull();
});

test('tournament repair propagates r32 winners into r16 fixtures', function () {
    finishImportedGroup('A');
    finishImportedGroup('B');

    $feeder = Fixture::query()->where('external_id', '73')->firstOrFail();

    $feeder->update([
        'status' => FixtureStatus::Final,
        'home_score' => 2,
        'away_score' => 1,
        'winner_team_id' => $feeder->home_team_id,
    ]);

    $slot = BracketSlot::query()
        ->where('slot_spec->match', '73')
        ->firstOrFail();

    $slot->update(['resolved_team_id' => null]);
    $slot->feedsFixture?->update([
        'home_team_id' => null,
        'away_team_id' => null,
    ]);

    $this->artisan('tournament:repair --propagate-r32')
        ->expectsOutputToContain('Propagated')
        ->assertSuccessful();

    $feedsFixture = $slot->feedsFixture?->fresh();

    expect($feedsFixture?->stage)->toBe(FixtureStage::Round16)
        ->and($feedsFixture?->home_team_id)->toBe($feeder->winner_team_id);
});

test('tournament repair verify reports missing winners', function () {
    $fixture = Fixture::query()->where('external_id', '76')->firstOrFail();

    $fixture->update([
        'status' => FixtureStatus::Final,
        'home_team_id' => Team::query()->firstOrFail()->id,
        'away_team_id' => Team::query()->skip(1)->firstOrFail()->id,
        'home_score' => 2,
        'away_score' => 1,
        'winner_team_id' => null,
    ]);

    $this->artisan('tournament:repair --verify')
        ->expectsOutputToContain('missing winner')
        ->assertSuccessful();
});

function finishImportedGroup(string $groupCode): void
{
    $fixtures = Fixture::query()
        ->where('stage', FixtureStage::Group)
        ->where('group_code', $groupCode)
        ->get();

    $leader = Team::query()->where('group_code', $groupCode)->orderByExternalId()->firstOrFail();

    foreach ($fixtures as $fixture) {
        $homeWins = $fixture->home_team_id === $leader->id;

        $fixture->update([
            'status' => FixtureStatus::Final,
            'home_score' => $homeWins ? 2 : 0,
            'away_score' => $homeWins ? 0 : 2,
            'winner_team_id' => $homeWins ? $fixture->home_team_id : $fixture->away_team_id,
        ]);
    }

    app(BracketResolverService::class)->resolveGroup($groupCode);
}
