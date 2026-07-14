<?php

use App\Models\Fixture;
use App\Models\FixtureMarket;
use App\Models\Team;
use Database\Seeders\PredictionMarketSeeder;

it('attaches enabled markets without changing fixture teams', function () {
    $this->seed(PredictionMarketSeeder::class);

    $home = Team::factory()->create(['name' => 'France']);
    $away = Team::factory()->create(['name' => 'Spain']);

    $fixture = Fixture::factory()->create([
        'external_id' => '101',
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
    ]);

    $this->artisan('markets:attach-enabled')
        ->expectsOutputToContain('Fixtures, scores, and bracket slots were not modified.')
        ->assertSuccessful();

    $fixture->refresh();

    expect($fixture->home_team_id)->toBe($home->id)
        ->and($fixture->away_team_id)->toBe($away->id)
        ->and(FixtureMarket::query()->where('fixture_id', $fixture->id)->count())->toBe(8);
});

it('is idempotent when run twice', function () {
    $this->seed(PredictionMarketSeeder::class);

    Fixture::factory()->create([
        'external_id' => '101',
        'home_team_id' => Team::factory()->create()->id,
        'away_team_id' => Team::factory()->create()->id,
    ]);

    $this->artisan('markets:attach-enabled')->assertSuccessful();
    $count = FixtureMarket::count();
    $this->artisan('markets:attach-enabled')->assertSuccessful();

    expect(FixtureMarket::count())->toBe($count);
});
