<?php

use App\Models\Fixture;
use App\Models\FixtureMarket;
use App\Models\Prediction;
use App\Models\PredictionMarket;
use App\Models\User;
use App\Predictions\Scoring\ScoringService;
use Illuminate\Database\QueryException;

it('scores a correct match-winner prediction with the market base points', function () {
    $fixtureMarket = FixtureMarket::factory()
        ->for(PredictionMarket::factory()->matchWinner(), 'market')
        ->settled(['winner' => 'home'])
        ->create();

    $prediction = Prediction::factory()
        ->for($fixtureMarket, 'fixtureMarket')
        ->create(['value' => ['selected' => 'home']]);

    expect(app(ScoringService::class)->score($prediction))->toBe(1);
});

it('doubles the points for a banker pick', function () {
    $fixtureMarket = FixtureMarket::factory()
        ->for(PredictionMarket::factory()->exactScore(), 'market')
        ->settled(['home' => 2, 'away' => 1])
        ->create();

    $prediction = Prediction::factory()
        ->banker()
        ->for($fixtureMarket, 'fixtureMarket')
        ->create(['value' => ['home' => 2, 'away' => 1]]);

    expect(app(ScoringService::class)->score($prediction))->toBe(6);
});

it('prefers the per-fixture base_points override over the catalogue value', function () {
    $fixtureMarket = FixtureMarket::factory()
        ->for(PredictionMarket::factory()->matchWinner(), 'market')
        ->settled(['winner' => 'home'])
        ->create(['base_points' => 5]);

    $prediction = Prediction::factory()
        ->for($fixtureMarket, 'fixtureMarket')
        ->create(['value' => ['selected' => 'home']]);

    expect(app(ScoringService::class)->score($prediction))->toBe(5);
});

it('returns zero for an unsettled market', function () {
    $fixtureMarket = FixtureMarket::factory()
        ->for(PredictionMarket::factory()->matchWinner(), 'market')
        ->create();

    $prediction = Prediction::factory()
        ->for($fixtureMarket, 'fixtureMarket')
        ->create(['value' => ['selected' => 'home']]);

    expect(app(ScoringService::class)->score($prediction))->toBe(0);
});

it('treats winner and exact-score as independent additive markets', function () {
    $fixture = Fixture::factory()->final(2, 1)->create();
    $user = User::factory()->create();

    $winnerMarket = FixtureMarket::factory()
        ->for($fixture)
        ->for(PredictionMarket::factory()->matchWinner(), 'market')
        ->settled(['winner' => 'home'])
        ->create();

    $scoreMarket = FixtureMarket::factory()
        ->for($fixture)
        ->for(PredictionMarket::factory()->exactScore(), 'market')
        ->settled(['home' => 2, 'away' => 1])
        ->create();

    $winnerPick = Prediction::factory()->for($user)->for($winnerMarket, 'fixtureMarket')
        ->create(['value' => ['selected' => 'home']]);
    $scorePick = Prediction::factory()->for($user)->for($scoreMarket, 'fixtureMarket')
        ->create(['value' => ['home' => 2, 'away' => 1]]);

    $service = app(ScoringService::class);

    expect($service->score($winnerPick) + $service->score($scorePick))->toBe(4);
});

it('allows only one prediction per user per market', function () {
    $fixtureMarket = FixtureMarket::factory()->create();
    $user = User::factory()->create();

    Prediction::factory()->for($user)->for($fixtureMarket, 'fixtureMarket')->create();
    Prediction::factory()->for($user)->for($fixtureMarket, 'fixtureMarket')->create();
})->throws(QueryException::class);
