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

function footballDataMatchesResponse(array $matches): array
{
    return [
        'filters' => ['season' => '2026', 'status' => 'FINISHED'],
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
            && $query['status'] === 'FINISHED'
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
