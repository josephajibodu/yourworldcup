<?php

use App\Models\Prediction;
use App\Models\User;
use App\Predictions\Leaderboard\LeaderboardService;
use App\Predictions\Settlement\SettlementService;
use Inertia\Testing\AssertableInertia as Assert;

it('ranks users by total points overall', function () {
    ['fixture' => $fixture, 'winner' => $winner, 'score' => $score] = finalFixtureWithMarkets(2, 1);
    $ada = User::factory()->create(['name' => 'Ada']);
    $bee = User::factory()->create(['name' => 'Bee']);
    User::factory()->create(['name' => 'Cara']);

    Prediction::factory()->for($ada)->create([
        'fixture_market_id' => $winner->id,
        'value' => ['selected' => 'home'],
    ]); // 1 pt
    Prediction::factory()->for($bee)->create([
        'fixture_market_id' => $score->id,
        'value' => ['home' => 2, 'away' => 1],
    ]); // 3 pts

    app(SettlementService::class)->settleFixture($fixture);

    $overall = app(LeaderboardService::class)->overall();

    expect($overall)->toHaveCount(2)
        ->and($overall[0])->toMatchArray(['name' => 'Bee', 'points' => 3, 'rank' => 1])
        ->and($overall[1])->toMatchArray(['name' => 'Ada', 'points' => 1, 'rank' => 2]);
});

it('breaks ties on total points using the earliest submitted_at', function () {
    ['fixture' => $fixture, 'winner' => $winner] = finalFixtureWithMarkets(1, 0);
    $ada = User::factory()->create(['name' => 'Ada']);
    $bee = User::factory()->create(['name' => 'Bee']);

    Prediction::factory()->for($ada)->create([
        'fixture_market_id' => $winner->id,
        'value' => ['selected' => 'home'],
        'submitted_at' => now()->subHours(3),
    ]);
    Prediction::factory()->for($bee)->create([
        'fixture_market_id' => $winner->id,
        'value' => ['selected' => 'home'],
        'submitted_at' => now()->subHour(),
    ]);

    app(SettlementService::class)->settleFixture($fixture);

    $overall = app(LeaderboardService::class)->overall();

    expect($overall)->toHaveCount(2)
        ->and($overall[0])->toMatchArray(['name' => 'Ada', 'points' => 1, 'rank' => 1])
        ->and($overall[1])->toMatchArray(['name' => 'Bee', 'points' => 1, 'rank' => 2]);
});

it('does not list registered users without predictions on the overall board', function () {
    User::factory()->create(['name' => 'New Player']);

    expect(app(LeaderboardService::class)->overall())->toHaveCount(0);
});

it('lists predictors on the overall board before their picks are scored', function () {
    ['fixture' => $fixture, 'winner' => $winner] = predictableFixture();
    $user = User::factory()->create(['name' => 'Early Bird']);

    Prediction::factory()->for($user)->create([
        'fixture_market_id' => $winner->id,
        'value' => ['selected' => 'home'],
    ]);

    $overall = app(LeaderboardService::class)->overall();

    expect($overall)->toHaveCount(1)
        ->and($overall->first())->toMatchArray(['name' => 'Early Bird', 'points' => 0, 'rank' => 1]);
});

it('scopes the daily board to the match day', function () {
    ['fixture' => $fixture, 'winner' => $winner] = finalFixtureWithMarkets(1, 0);
    $user = User::factory()->create();
    Prediction::factory()->for($user)->create([
        'fixture_market_id' => $winner->id,
        'value' => ['selected' => 'home'],
    ]);

    app(SettlementService::class)->settleFixture($fixture);

    $watDate = $fixture->watDate();
    $service = app(LeaderboardService::class);

    expect($service->daily($watDate))->toHaveCount(1)
        ->and($service->daily('2026-01-01'))->toHaveCount(0);
});

it('lists daily participants who predicted before results are scored', function () {
    ['fixture' => $fixture, 'winner' => $winner] = predictableFixture();
    $user = User::factory()->create(['name' => 'Early Bird']);

    Prediction::factory()->for($user)->create([
        'fixture_market_id' => $winner->id,
        'value' => ['selected' => 'home'],
    ]);

    $daily = app(LeaderboardService::class)->daily($fixture->watDate());

    expect($daily)->toHaveCount(1)
        ->and($daily->first())->toMatchArray(['name' => 'Early Bird', 'points' => 0, 'rank' => 1]);
});

it('renders the leaderboard page publicly with both boards', function () {
    ['fixture' => $fixture, 'winner' => $winner] = finalFixtureWithMarkets(1, 0);
    Prediction::factory()->for(User::factory()->create(['name' => 'Ada']))->create([
        'fixture_market_id' => $winner->id,
        'value' => ['selected' => 'home'],
    ]);
    app(SettlementService::class)->settleFixture($fixture);

    $this->get(route('leaderboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('leaderboard')
            ->where('selectedDate', $fixture->watDate())
            ->has('overall', 1)
            ->has('daily', 1)
            ->where('overall.0.name', 'Ada')
        );
});
