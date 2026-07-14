<?php

use App\Bracket\BracketSlotImporter;
use App\Enums\BracketSlotSide;
use App\Enums\FixtureStatus;
use App\Models\BracketSlot;
use App\Models\Fixture;
use App\Models\Team;
use App\Predictions\Importing\WorldCupImporter;
use Database\Seeders\PredictionMarketSeeder;

beforeEach(function () {
    $this->seed(PredictionMarketSeeder::class);
    WorldCupImporter::fromDefaultPath()->import();
});

it('preserves resolved knockout teams when re-importing stale json placeholders', function () {
    $france = Team::factory()->create(['name' => 'France']);
    $spain = Team::factory()->create(['name' => 'Spain']);

    Fixture::query()->where('external_id', '101')->update([
        'home_team_id' => $france->id,
        'away_team_id' => $spain->id,
    ]);

    WorldCupImporter::fromDefaultPath()->importFixtures();

    $fixture = Fixture::query()->where('external_id', '101')->firstOrFail();

    expect($fixture->home_team_id)->toBe($france->id)
        ->and($fixture->away_team_id)->toBe($spain->id);
});

it('preserves final fixture results when json still marks the match unfinished', function () {
    $home = Team::factory()->create();
    $away = Team::factory()->create();

    Fixture::query()->where('external_id', '101')->update([
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
        'status' => FixtureStatus::Final,
        'home_score' => 2,
        'away_score' => 1,
        'winner_team_id' => $home->id,
    ]);

    WorldCupImporter::fromDefaultPath()->importFixtures();

    $fixture = Fixture::query()->where('external_id', '101')->firstOrFail();

    expect($fixture->status)->toBe(FixtureStatus::Final)
        ->and($fixture->home_score)->toBe(2)
        ->and($fixture->away_score)->toBe(1)
        ->and($fixture->winner_team_id)->toBe($home->id);
});

it('preserves resolved bracket teams when re-importing bracket slots', function () {
    $fixture = Fixture::query()->where('external_id', '101')->firstOrFail();
    $team = Team::factory()->create(['name' => 'France']);

    $slot = BracketSlot::query()
        ->where('feeds_fixture_id', $fixture->id)
        ->where('side', BracketSlotSide::Home)
        ->firstOrFail();

    $slot->update(['resolved_team_id' => $team->id]);

    BracketSlotImporter::fromDefaultPath()->import();

    expect($slot->fresh()->resolved_team_id)->toBe($team->id);
});
