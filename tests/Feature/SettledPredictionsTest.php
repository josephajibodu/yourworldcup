<?php

use App\Models\Prediction;
use App\Models\Referral;
use App\Models\User;
use App\Predictions\Settlement\SettlementService;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it('requires authentication to view settled predictions', function () {
    $this->get(route('predict.history'))
        ->assertRedirect(route('login'));
});

it('shows settled predictions grouped by fixture with summary stats', function () {
    $user = User::factory()->create();
    ['fixture' => $fixture, 'winner' => $winner, 'score' => $score] = finalFixtureWithMarkets(2, 1);

    Prediction::factory()->for($user)->create([
        'fixture_market_id' => $winner->id,
        'value' => ['selected' => 'home'],
    ]);

    Prediction::factory()->for($user)->banker()->create([
        'fixture_market_id' => $score->id,
        'value' => ['home' => 0, 'away' => 0],
    ]);

    app(SettlementService::class)->settleFixture($fixture);

    Referral::factory()->create([
        'referrer_id' => $user->id,
        'points' => 1,
    ]);

    actingAs($user)
        ->get(route('predict.history'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('predict/history')
            ->where('summary.predictionPoints', 1)
            ->where('summary.referralPoints', 1)
            ->has('fixtures.data', 1)
            ->where('fixtures.data.0.id', $fixture->id)
            ->where('fixtures.data.0.totalPoints', 1)
            ->has('fixtures.data.0.predictions', 2)
            ->where('fixtures.data.0.predictions.0.outcome', 'won')
            ->where('fixtures.data.0.predictions.1.outcome', 'lost')
            ->where('fixtures.data.0.predictions.1.isBanker', true));
});

it('orders settled fixtures by most recent kickoff first', function () {
    $user = User::factory()->create();
    $fixturesByRecency = [];

    foreach ([5, 1, 3] as $daysAgo) {
        ['fixture' => $fixture, 'winner' => $winner] = finalFixtureWithMarkets(1, 0);

        $kickoff = now()->subDays($daysAgo);

        $fixture->update([
            'kickoff_at' => $kickoff,
            'lock_at' => $kickoff->copy()->subHour(),
        ]);

        Prediction::factory()->for($user)->create([
            'fixture_market_id' => $winner->id,
            'value' => ['selected' => 'home'],
        ]);

        app(SettlementService::class)->settleFixture($fixture);

        $fixturesByRecency[] = $fixture->id;
    }

    actingAs($user)
        ->get(route('predict.history'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('fixtures.data.0.id', $fixturesByRecency[1])
            ->where('fixtures.data.1.id', $fixturesByRecency[2])
            ->where('fixtures.data.2.id', $fixturesByRecency[0]));
});

it('orders fixtures with the same kickoff by highest id first', function () {
    $user = User::factory()->create();
    $fixtureIds = [];

    foreach (range(1, 3) as $index) {
        ['fixture' => $fixture, 'winner' => $winner] = finalFixtureWithMarkets(1, 0);

        Prediction::factory()->for($user)->create([
            'fixture_market_id' => $winner->id,
            'value' => ['selected' => 'home'],
        ]);

        app(SettlementService::class)->settleFixture($fixture);

        $fixtureIds[] = $fixture->id;
    }

    actingAs($user)
        ->get(route('predict.history'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('fixtures.data.0.id', $fixtureIds[2])
            ->where('fixtures.data.1.id', $fixtureIds[1])
            ->where('fixtures.data.2.id', $fixtureIds[0]));
});

it('paginates settled fixtures ten per page', function () {
    $user = User::factory()->create();

    foreach (range(1, 11) as $index) {
        ['fixture' => $fixture, 'winner' => $winner] = finalFixtureWithMarkets(1, 0);

        $fixture->update([
            'kickoff_at' => now()->subDays($index),
            'lock_at' => now()->subDays($index)->subHour(),
        ]);

        Prediction::factory()->for($user)->create([
            'fixture_market_id' => $winner->id,
            'value' => ['selected' => 'home'],
        ]);

        app(SettlementService::class)->settleFixture($fixture);
    }

    actingAs($user)
        ->get(route('predict.history'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('fixtures.data', 10)
            ->where('fixtures.total', 11)
            ->where('fixtures.per_page', 10));

    actingAs($user)
        ->get(route('predict.history', ['page' => 2]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('fixtures.data', 1)
            ->where('fixtures.current_page', 2));
});

it('excludes fixtures with only unscored predictions', function () {
    $user = User::factory()->create();
    ['fixture' => $fixture, 'winner' => $winner] = predictableFixture(-1);

    Prediction::factory()->for($user)->create([
        'fixture_market_id' => $winner->id,
        'value' => ['selected' => 'home'],
    ]);

    actingAs($user)
        ->get(route('predict.history'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('predict/history')
            ->where('summary.predictionPoints', 0)
            ->where('summary.referralPoints', 0)
            ->where('fixtures.total', 0));
});
