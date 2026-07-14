<?php

use App\Enums\FixtureStage;
use App\Models\Fixture;
use App\Models\Team;

it('shows a fixture by match number', function () {
    $home = Team::factory()->create(['name' => 'France', 'code' => 'FRA']);
    $away = Team::factory()->create(['name' => 'Spain', 'code' => 'ESP']);

    Fixture::factory()->create([
        'external_id' => '101',
        'stage' => FixtureStage::SemiFinal,
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
    ]);

    $this->artisan('fixture:show', ['match' => 'M101'])
        ->expectsOutputToContain('M101')
        ->expectsOutputToContain('France vs Spain')
        ->expectsOutputToContain('FRA vs ESP')
        ->assertSuccessful();
});

it('accepts match numbers without the M prefix', function () {
    $home = Team::factory()->create(['name' => 'France']);
    $away = Team::factory()->create(['name' => 'Spain']);

    Fixture::factory()->create([
        'external_id' => '101',
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
    ]);

    $this->artisan('fixture:show', ['match' => '101'])
        ->expectsOutputToContain('France vs Spain')
        ->assertSuccessful();
});

it('fails when the match number is not found', function () {
    $this->artisan('fixture:show', ['match' => '999'])
        ->assertFailed();
});
