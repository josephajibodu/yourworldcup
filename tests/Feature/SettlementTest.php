<?php

use App\Enums\FixtureStatus;
use App\Enums\MarketStatus;
use App\Models\Fixture;
use App\Models\FixtureMarket;
use App\Models\Prediction;
use App\Models\PredictionMarket;
use App\Models\Team;
use App\Models\User;
use App\Predictions\Settlement\SettlementService;

it('settles markets and scores correct picks, doubling the banker', function () {
    ['fixture' => $fixture, 'winner' => $winner, 'score' => $score] = finalFixtureWithMarkets(2, 1);
    $picker = User::factory()->create();
    $banker = User::factory()->create();

    Prediction::factory()->for($picker)->create([
        'fixture_market_id' => $winner->id,
        'value' => ['selected' => 'home'],
    ]);
    Prediction::factory()->for($banker)->banker()->create([
        'fixture_market_id' => $score->id,
        'value' => ['home' => 2, 'away' => 1],
    ]);

    app(SettlementService::class)->settleFixture($fixture);

    expect($winner->fresh()->status)->toBe(MarketStatus::Settled)
        ->and($winner->fresh()->settlement_value)->toBe(['winner' => 'home'])
        ->and($score->fresh()->settlement_value)->toBe(['home' => 2, 'away' => 1])
        ->and(Prediction::query()->where('user_id', $picker->id)->sole()->points_awarded)->toBe(1)
        ->and(Prediction::query()->where('user_id', $banker->id)->sole()->points_awarded)->toBe(6);
});

it('scores an incorrect pick zero', function () {
    ['fixture' => $fixture, 'winner' => $winner] = finalFixtureWithMarkets(0, 0);

    Prediction::factory()->for(User::factory())->create([
        'fixture_market_id' => $winner->id,
        'value' => ['selected' => 'home'],
    ]);

    app(SettlementService::class)->settleFixture($fixture);

    expect($winner->fresh()->settlement_value)->toBe(['winner' => 'draw'])
        ->and(Prediction::query()->sole()->points_awarded)->toBe(0);
});

it('voids markets and zeroes predictions on a void fixture', function () {
    $kickoff = now()->subHours(3);
    $fixture = Fixture::factory()->create([
        'status' => FixtureStatus::Void,
        'home_team_id' => Team::factory()->create()->id,
        'away_team_id' => Team::factory()->create()->id,
        'kickoff_at' => $kickoff,
        'lock_at' => $kickoff->copy()->subHour(),
    ]);
    $market = FixtureMarket::factory()->create([
        'fixture_id' => $fixture->id,
        'prediction_market_id' => PredictionMarket::factory()->matchWinner()->create()->id,
    ]);
    Prediction::factory()->for(User::factory())->banker()->create([
        'fixture_market_id' => $market->id,
        'value' => ['selected' => 'home'],
    ]);

    app(SettlementService::class)->settleFixture($fixture);

    expect($market->fresh()->status)->toBe(MarketStatus::Void)
        ->and(Prediction::query()->sole()->points_awarded)->toBe(0);
});

it('does not score an unfinished fixture', function () {
    ['fixture' => $fixture, 'winner' => $winner] = finalFixtureWithMarkets(1, 0);
    $fixture->update(['status' => FixtureStatus::Live]);

    Prediction::factory()->for(User::factory())->create([
        'fixture_market_id' => $winner->id,
        'value' => ['selected' => 'home'],
    ]);

    app(SettlementService::class)->settleFixture($fixture);

    expect($winner->fresh()->status)->toBe(MarketStatus::Open)
        ->and(Prediction::query()->sole()->points_awarded)->toBeNull();
});

it('settles pending finished fixtures via the command and is idempotent', function () {
    ['winner' => $winner] = finalFixtureWithMarkets(1, 0);
    Prediction::factory()->for(User::factory())->create([
        'fixture_market_id' => $winner->id,
        'value' => ['selected' => 'home'],
    ]);

    $this->artisan('predictions:settle')->assertSuccessful();
    $this->artisan('predictions:settle')->assertSuccessful();

    expect($winner->fresh()->status)->toBe(MarketStatus::Settled)
        ->and(Prediction::query()->sole()->points_awarded)->toBe(1);
});
