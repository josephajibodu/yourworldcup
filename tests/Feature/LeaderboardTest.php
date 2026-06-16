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
        ->and($overall->first())->toMatchArray(['name' => 'Bee', 'points' => 3, 'rank' => 1])
        ->and($overall->last())->toMatchArray(['name' => 'Ada', 'points' => 1, 'rank' => 2]);
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
