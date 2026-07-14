<?php

use App\Enums\FixtureStatus;
use App\Models\Fixture;
use App\Predictions\Importing\WorldCupImporter;
use Database\Seeders\PredictionMarketSeeder;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->seed(PredictionMarketSeeder::class);
    WorldCupImporter::fromDefaultPath()->import();
});

it('restores finished scores after import wiped them', function () {
    $fixture = Fixture::query()->where('external_id', '1')->firstOrFail();
    $homeTeamId = $fixture->home_team_id;
    $awayTeamId = $fixture->away_team_id;

    $fixture->update([
        'provider' => 'football-data',
        'provider_match_id' => '537327',
        'status' => FixtureStatus::Scheduled,
        'home_score' => null,
        'away_score' => null,
        'winner_team_id' => null,
    ]);

    Http::fake([
        'api.football-data.org/v4/competitions/2000/matches*' => Http::response([
            'matches' => [[
                'id' => 537327,
                'status' => 'FINISHED',
                'score' => [
                    'duration' => 'REGULAR',
                    'fullTime' => ['home' => 2, 'away' => 0],
                    'winner' => 'HOME_TEAM',
                ],
            ]],
        ]),
    ]);

    $this->artisan('tournament:recover')
        ->expectsOutputToContain('Tournament recovery complete.')
        ->assertSuccessful();

    $fixture->refresh();

    expect($fixture->status)->toBe(FixtureStatus::Final)
        ->and($fixture->home_score)->toBe(2)
        ->and($fixture->away_score)->toBe(0)
        ->and($fixture->home_team_id)->toBe($homeTeamId)
        ->and($fixture->away_team_id)->toBe($awayTeamId)
        ->and($fixture->winner_team_id)->toBe($homeTeamId);
});

it('can skip football-data sync', function () {
    $this->artisan('tournament:recover', ['--no-sync' => true])
        ->assertSuccessful();
});
