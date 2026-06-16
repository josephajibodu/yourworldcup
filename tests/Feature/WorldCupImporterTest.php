<?php

use App\Models\Fixture;
use App\Models\FixtureMarket;
use App\Models\Stadium;
use App\Models\Team;
use App\Predictions\Importing\WorldCupImporter;
use Database\Seeders\PredictionMarketSeeder;

beforeEach(function () {
    $this->seed(PredictionMarketSeeder::class);
    WorldCupImporter::fromDefaultPath()->import();
});

it('imports every stadium, team and fixture', function () {
    expect(Stadium::count())->toBe(16)
        ->and(Team::count())->toBe(48)
        ->and(Fixture::count())->toBe(104);
});

it('flags the ten African nations from iso2', function () {
    expect(Team::query()->where('is_african', true)->count())->toBe(10);
});

it('converts venue-local kickoff to a UTC instant and derives the lock time', function () {
    $opener = Fixture::query()->where('external_id', '1')->firstOrFail();

    // 06/11/2026 13:00 at Estadio Azteca (Mexico City, UTC-6) => 19:00 UTC.
    expect($opener->kickoff_at->utc()->toDateTimeString())->toBe('2026-06-11 19:00:00')
        ->and($opener->lock_at->utc()->toDateTimeString())->toBe('2026-06-11 18:00:00');
});

it('imports knockout fixtures with unresolved teams and no group', function () {
    $roundOf32 = Fixture::query()->where('external_id', '73')->firstOrFail();

    expect($roundOf32->stage->value)->toBe('r32')
        ->and($roundOf32->home_team_id)->toBeNull()
        ->and($roundOf32->away_team_id)->toBeNull()
        ->and($roundOf32->group_code)->toBeNull();
});

it('attaches every enabled market to every fixture', function () {
    // 2 enabled markets (winner + exact score) across 104 fixtures.
    expect(FixtureMarket::count())->toBe(208);
});

it('is idempotent when re-run', function () {
    WorldCupImporter::fromDefaultPath()->import();

    expect(Team::count())->toBe(48)
        ->and(Fixture::count())->toBe(104)
        ->and(FixtureMarket::count())->toBe(208);
});
