<?php

use App\FootballData\FootballDataLinker;
use App\Models\Fixture;
use App\Models\Team;
use App\Predictions\Importing\WorldCupImporter;
use Database\Seeders\PredictionMarketSeeder;
use Database\Seeders\WorldCupSeeder;
use Illuminate\Support\Facades\Artisan;

beforeEach(function () {
    $this->seed(PredictionMarketSeeder::class);
    WorldCupImporter::fromDefaultPath()->import();
});

it('links teams and fixtures from the football-data org json exports', function () {
    $linker = FootballDataLinker::fromConfig();

    $teams = $linker->linkTeams();
    $fixtures = $linker->linkFixtures();

    expect($teams['unmatched'])->toBe([])
        ->and($fixtures['unmatched'])->toBe([])
        ->and($teams['linked'])->toBe(48)
        ->and($fixtures['linked'])->toBe(104);

    $mexico = Team::query()->where('code', 'MEX')->firstOrFail();
    $opener = Fixture::query()->where('external_id', '1')->firstOrFail();
    $roundOf32 = Fixture::query()->where('external_id', '73')->firstOrFail();

    expect($mexico->provider)->toBe('football-data')
        ->and($mexico->provider_team_id)->toBe('769')
        ->and($opener->provider)->toBe('football-data')
        ->and($opener->provider_match_id)->toBe('537327')
        ->and($roundOf32->provider_match_id)->toBe('537417')
        ->and(Fixture::findByProviderMatch('football-data', '537327')?->id)->toBe($opener->id);
});

it('is idempotent when re-run', function () {
    $linker = FootballDataLinker::fromConfig();

    $linker->linkTeams();
    $linker->linkFixtures();

    $teams = $linker->linkTeams();
    $fixtures = $linker->linkFixtures();

    expect($teams['linked'])->toBe(0)
        ->and($teams['skipped'])->toBe(48)
        ->and($fixtures['linked'])->toBe(0)
        ->and($fixtures['skipped'])->toBe(104);
});

it('links ids through the artisan command', function () {
    $this->artisan('football-data:link')->assertSuccessful();

    expect(Team::query()->where('provider', 'football-data')->count())->toBe(48)
        ->and(Fixture::query()->where('provider', 'football-data')->count())->toBe(104);
});

it('links ids when worldcup import runs', function () {
    Artisan::call('worldcup:import');

    expect(Fixture::query()->where('provider', 'football-data')->count())->toBe(104)
        ->and(Team::query()->where('provider', 'football-data')->count())->toBe(48);
});

it('links ids when the world cup seeder runs', function () {
    $this->seed(WorldCupSeeder::class);

    expect(Fixture::query()->where('provider', 'football-data')->count())->toBe(104)
        ->and(Team::query()->where('provider', 'football-data')->count())->toBe(48);
});
