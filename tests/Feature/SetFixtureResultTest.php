<?php

use App\Fixtures\FixtureResultRecorder;
use App\Models\Fixture;
use App\Models\Team;
use Database\Seeders\PredictionMarketSeeder;
use Illuminate\Support\Facades\Artisan;

beforeEach(function () {
    $this->seed(PredictionMarketSeeder::class);
});

it('records a score interactively by fixture id', function () {
    $home = Team::factory()->create(['name' => 'Mexico']);
    $away = Team::factory()->create(['name' => 'Spain']);

    Fixture::factory()->create([
        'external_id' => '501',
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
    ]);

    $this->artisan('fixture:result', ['fixture' => '501'])
        ->expectsQuestion('Home score', 2)
        ->expectsQuestion('Away score', 1)
        ->assertSuccessful();

    $fixture = Fixture::query()->where('external_id', '501')->firstOrFail();

    expect($fixture->status->value)->toBe('final')
        ->and($fixture->home_score)->toBe(2)
        ->and($fixture->away_score)->toBe(1)
        ->and($fixture->winner_team_id)->toBe($home->id);
});

it('records bulk results from json', function () {
    $home = Team::factory()->create();
    $away = Team::factory()->create();

    Fixture::factory()->create([
        'external_id' => '601',
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
    ]);

    Fixture::factory()->create([
        'external_id' => '602',
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
    ]);

    $json = json_encode([
        ['id' => '601', 'home' => 1, 'away' => 0],
        ['id' => '602', 'home_score' => 2, 'away_score' => 2],
    ], JSON_THROW_ON_ERROR);

    Artisan::call('fixture:result', ['--json' => $json]);

    expect(Artisan::output())->toContain('Recorded 2 of 2');

    expect(Fixture::query()->where('external_id', '601')->first())
        ->home_score->toBe(1)
        ->away_score->toBe(0)
        ->winner_team_id->toBe($home->id);

    expect(Fixture::query()->where('external_id', '602')->first())
        ->home_score->toBe(2)
        ->away_score->toBe(2)
        ->winner_team_id->toBeNull();
});

it('rejects invalid bulk json', function () {
    Artisan::call('fixture:result', ['--json' => '{"id":1}']);

    expect(Artisan::output())->toContain('Bulk results must be a JSON array');
});

it('parses bulk entries with alternate score keys', function () {
    $entries = app(FixtureResultRecorder::class)->parseBulkInput(json_encode([
        ['id' => 7, 'home_score' => 3, 'away_score' => 1],
    ], JSON_THROW_ON_ERROR));

    expect($entries)->toBe([
        ['id' => '7', 'home' => 3, 'away' => 1],
    ]);
});
