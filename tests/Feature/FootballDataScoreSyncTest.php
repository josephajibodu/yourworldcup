<?php

use App\Enums\FixtureStatus;
use App\Enums\MarketStatus;
use App\FootballData\FootballDataLinker;
use App\Models\Fixture;
use App\Models\Prediction;
use App\Models\User;
use App\Predictions\Importing\WorldCupImporter;
use Database\Seeders\PredictionMarketSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'football_data.api_token' => 'test-token',
        'football_data.competition_id' => 2000,
        'football_data.season' => 2026,
    ]);

    Http::preventStrayRequests();

    $this->seed(PredictionMarketSeeder::class);
    WorldCupImporter::fromDefaultPath()->import();
    FootballDataLinker::fromConfig()->linkTeams();
    FootballDataLinker::fromConfig()->linkFixtures();
});

function footballDataMatchesResponse(array $matches, array $filters = ['season' => '2026']): array
{
    return [
        'filters' => $filters,
        'resultSet' => ['count' => count($matches)],
        'competition' => ['id' => 2000, 'name' => 'FIFA World Cup', 'code' => 'WC'],
        'matches' => $matches,
    ];
}

function finishedFootballDataMatch(int $id, int $home, int $away, string $utcDate = '2026-06-11T19:00:00Z'): array
{
    return [
        'id' => $id,
        'utcDate' => $utcDate,
        'status' => 'FINISHED',
        'score' => [
            'fullTime' => [
                'home' => $home,
                'away' => $away,
            ],
        ],
    ];
}

function finishedFootballDataMatchAfterPenalties(
    int $id,
    int $regularHome,
    int $regularAway,
    string $overallWinner,
    string $utcDate = '2026-06-30T01:00:00Z',
): array {
    return [
        'id' => $id,
        'utcDate' => $utcDate,
        'status' => 'FINISHED',
        'score' => [
            'winner' => $overallWinner,
            'duration' => 'PENALTY_SHOOTOUT',
            'fullTime' => [
                'home' => $regularHome + 2,
                'away' => $regularAway + 3,
            ],
            'regularTime' => [
                'home' => $regularHome,
                'away' => $regularAway,
            ],
            'extraTime' => [
                'home' => 0,
                'away' => 0,
            ],
            'penalties' => [
                'home' => 2,
                'away' => 3,
            ],
        ],
    ];
}

function liveFootballDataMatch(
    int $id,
    string $utcDate = '2026-06-11T19:00:00Z',
    ?int $home = null,
    ?int $away = null,
): array {
    return [
        'id' => $id,
        'utcDate' => $utcDate,
        'status' => 'IN_PLAY',
        'score' => [
            'fullTime' => [
                'home' => $home,
                'away' => $away,
            ],
        ],
    ];
}

it('syncs recently finished matches and settles predictions', function () {
    $fixture = Fixture::query()->where('external_id', '1')->firstOrFail();
    ['matchWinner' => $matchWinner] = predictionMarkets();

    $winner = $fixture->markets()->where('prediction_market_id', $matchWinner->id)->firstOrFail();

    Prediction::factory()->for(User::factory())->create([
        'fixture_market_id' => $winner->id,
        'value' => ['selected' => 'home'],
    ]);

    $today = Carbon::now(config('predictions.timezone'))->toDateString();
    $yesterday = Carbon::now(config('predictions.timezone'))->subDay()->toDateString();

    Http::fake([
        'api.football-data.org/v4/competitions/2000/matches*' => Http::response(
            footballDataMatchesResponse([
                finishedFootballDataMatch(537327, 2, 0),
            ]),
        ),
    ]);

    $this->artisan('football-data:sync-scores')->assertSuccessful();

    Http::assertSent(function ($request) use ($today, $yesterday) {
        parse_str(parse_url($request->url(), PHP_URL_QUERY) ?? '', $query);

        return $query['season'] === '2026'
            && ! array_key_exists('status', $query)
            && $query['dateFrom'] === $yesterday
            && $query['dateTo'] === $today;
    });

    $fixture->refresh();

    expect($fixture->status)->toBe(FixtureStatus::Final)
        ->and($fixture->home_score)->toBe(2)
        ->and($fixture->away_score)->toBe(0)
        ->and($winner->fresh()->status)->toBe(MarketStatus::Settled)
        ->and(Prediction::query()->sole()->points_awarded)->toBe(1);
});

it('syncs all played matches without settling by default', function () {
    $fixture = Fixture::query()->where('external_id', '1')->firstOrFail();
    ['matchWinner' => $matchWinner] = predictionMarkets();

    $winner = $fixture->markets()->where('prediction_market_id', $matchWinner->id)->firstOrFail();

    Prediction::factory()->for(User::factory())->create([
        'fixture_market_id' => $winner->id,
        'value' => ['selected' => 'home'],
    ]);

    Http::fake([
        'api.football-data.org/v4/competitions/2000/matches*' => Http::response(
            footballDataMatchesResponse([
                finishedFootballDataMatch(537327, 2, 0),
            ]),
        ),
    ]);

    $this->artisan('football-data:sync-played')->assertSuccessful();

    Http::assertSent(function ($request) {
        parse_str(parse_url($request->url(), PHP_URL_QUERY) ?? '', $query);

        return $query['season'] === '2026'
            && $query['status'] === 'FINISHED'
            && ! array_key_exists('dateFrom', $query)
            && ! array_key_exists('dateTo', $query);
    });

    $fixture->refresh();

    expect($fixture->status)->toBe(FixtureStatus::Final)
        ->and($fixture->home_score)->toBe(2)
        ->and($fixture->away_score)->toBe(0)
        ->and($winner->fresh()->status)->toBe(MarketStatus::Open)
        ->and(Prediction::query()->sole()->points_awarded)->toBeNull();
});

it('marks in-play matches as live without recording scores when none are available', function () {
    $fixture = Fixture::query()->where('external_id', '1')->firstOrFail();

    expect($fixture->status)->toBe(FixtureStatus::Scheduled);

    Http::fake([
        'api.football-data.org/v4/competitions/2000/matches*' => Http::response(
            footballDataMatchesResponse([
                liveFootballDataMatch(537327),
            ]),
        ),
    ]);

    $this->artisan('football-data:sync-scores')
        ->expectsOutputToContain('updated 1')
        ->assertSuccessful();

    $fixture->refresh();

    expect($fixture->status)->toBe(FixtureStatus::Live)
        ->and($fixture->home_score)->toBeNull()
        ->and($fixture->away_score)->toBeNull();
});

it('records live scores without settling predictions', function () {
    $fixture = Fixture::query()->where('external_id', '1')->firstOrFail();
    ['matchWinner' => $matchWinner] = predictionMarkets();

    $winner = $fixture->markets()->where('prediction_market_id', $matchWinner->id)->firstOrFail();

    Prediction::factory()->for(User::factory())->create([
        'fixture_market_id' => $winner->id,
        'value' => ['selected' => 'home'],
    ]);

    Http::fake([
        'api.football-data.org/v4/competitions/2000/matches*' => Http::response(
            footballDataMatchesResponse([
                liveFootballDataMatch(537327, home: 1, away: 0),
            ]),
        ),
    ]);

    $this->artisan('football-data:sync-scores')
        ->expectsOutputToContain('updated 1')
        ->assertSuccessful();

    $fixture->refresh();

    expect($fixture->status)->toBe(FixtureStatus::Live)
        ->and($fixture->home_score)->toBe(1)
        ->and($fixture->away_score)->toBe(0)
        ->and($winner->fresh()->status)->toBe(MarketStatus::Open)
        ->and(Prediction::query()->sole()->points_awarded)->toBeNull();
});

it('updates live scores on subsequent syncs', function () {
    $fixture = Fixture::query()->where('external_id', '1')->firstOrFail();
    $fixture->update([
        'status' => FixtureStatus::Live,
        'home_score' => 1,
        'away_score' => 0,
    ]);

    Http::fake([
        'api.football-data.org/v4/competitions/2000/matches*' => Http::response(
            footballDataMatchesResponse([
                liveFootballDataMatch(537327, home: 2, away: 1),
            ]),
        ),
    ]);

    $this->artisan('football-data:sync-scores')
        ->expectsOutputToContain('updated 1')
        ->assertSuccessful();

    $fixture->refresh();

    expect($fixture->status)->toBe(FixtureStatus::Live)
        ->and($fixture->home_score)->toBe(2)
        ->and($fixture->away_score)->toBe(1);
});

it('skips fixtures that already have the same final score', function () {
    $fixture = Fixture::query()->where('external_id', '1')->firstOrFail();
    $fixture->update([
        'status' => FixtureStatus::Final,
        'home_score' => 2,
        'away_score' => 0,
    ]);

    Http::fake([
        'api.football-data.org/v4/competitions/2000/matches*' => Http::response(
            footballDataMatchesResponse([
                finishedFootballDataMatch(537327, $fixture->home_score, $fixture->away_score),
            ]),
        ),
    ]);

    $this->artisan('football-data:sync-played')
        ->expectsOutputToContain('updated 0')
        ->assertSuccessful();
});

it('fails when the api token is missing', function () {
    config(['football_data.api_token' => null]);

    $this->artisan('football-data:sync-scores')
        ->expectsOutputToContain('FOOTBALL_DATA_API_TOKEN is not configured.')
        ->assertFailed();
});

it('uses regular time scores for penalty shootouts and keeps the advancing team', function () {
    $fixture = Fixture::query()->where('external_id', '1')->firstOrFail();
    ['matchWinner' => $matchWinner, 'exactScore' => $exactScore] = predictionMarkets();

    $winner = $fixture->markets()->where('prediction_market_id', $matchWinner->id)->firstOrFail();
    $score = $fixture->markets()->where('prediction_market_id', $exactScore->id)->firstOrFail();

    $drawPicker = User::factory()->create();
    $scorePicker = User::factory()->create();

    Prediction::factory()->for($drawPicker)->create([
        'fixture_market_id' => $winner->id,
        'value' => ['selected' => 'draw'],
    ]);
    Prediction::factory()->for($scorePicker)->create([
        'fixture_market_id' => $score->id,
        'value' => ['home' => 1, 'away' => 1],
    ]);

    Http::fake([
        'api.football-data.org/v4/competitions/2000/matches*' => Http::response(
            footballDataMatchesResponse([
                finishedFootballDataMatchAfterPenalties(537327, 1, 1, 'AWAY_TEAM'),
            ]),
        ),
    ]);

    $this->artisan('football-data:sync-scores')->assertSuccessful();

    $fixture->refresh();

    expect($fixture->status)->toBe(FixtureStatus::Final)
        ->and($fixture->home_score)->toBe(1)
        ->and($fixture->away_score)->toBe(1)
        ->and($fixture->winner_team_id)->toBe($fixture->away_team_id)
        ->and($winner->fresh()->status)->toBe(MarketStatus::Settled)
        ->and($winner->fresh()->settlement_value)->toBe(['winner' => 'draw'])
        ->and($score->fresh()->status)->toBe(MarketStatus::Settled)
        ->and($score->fresh()->settlement_value)->toBe(['home' => 1, 'away' => 1])
        ->and(Prediction::query()->where('user_id', $drawPicker->id)->sole()->points_awarded)->toBe(1)
        ->and(Prediction::query()->where('user_id', $scorePicker->id)->sole()->points_awarded)->toBe(3);
});

it('re-settles predictions when a final score is corrected to regular time', function () {
    $fixture = Fixture::query()->where('external_id', '1')->firstOrFail();
    ['exactScore' => $exactScore] = predictionMarkets();

    $score = $fixture->markets()->where('prediction_market_id', $exactScore->id)->firstOrFail();
    $picker = User::factory()->create();

    $fixture->update([
        'status' => FixtureStatus::Final,
        'home_score' => 3,
        'away_score' => 4,
        'winner_team_id' => $fixture->away_team_id,
    ]);

    $score->update([
        'status' => MarketStatus::Settled,
        'settlement_value' => ['home' => 3, 'away' => 4],
        'settled_at' => now(),
    ]);

    Prediction::factory()->for($picker)->create([
        'fixture_market_id' => $score->id,
        'value' => ['home' => 1, 'away' => 1],
        'points_awarded' => 0,
        'scored_at' => now(),
    ]);

    Http::fake([
        'api.football-data.org/v4/competitions/2000/matches*' => Http::response(
            footballDataMatchesResponse([
                finishedFootballDataMatchAfterPenalties(537327, 1, 1, 'AWAY_TEAM'),
            ]),
        ),
    ]);

    $this->artisan('football-data:sync-scores')->assertSuccessful();

    $fixture->refresh();

    expect($fixture->home_score)->toBe(1)
        ->and($fixture->away_score)->toBe(1)
        ->and($score->fresh()->settlement_value)->toBe(['home' => 1, 'away' => 1])
        ->and(Prediction::query()->where('user_id', $picker->id)->sole()->points_awarded)->toBe(3);
});
