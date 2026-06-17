<?php

use App\Models\Fixture;
use App\Models\FixtureMarket;
use App\Models\Prediction;
use App\Models\PredictionMarket;
use App\Models\Team;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it('requires authentication to predict', function () {
    $this->get(route('predict'))->assertRedirect(route('login'));
});

it('shows the selected day fixtures to a signed-in user', function () {
    $fixture = predictableFixture()['fixture'];

    actingAs(User::factory()->create())
        ->get(route('predict', ['date' => $fixture->watDate()]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('predict')
            ->where('selectedDate', $fixture->watDate())
            ->has('fixtures', 1)
            ->has('fixtures.0.markets', 2)
        );
});

it('saves a users picks for the day', function () {
    ['fixture' => $fixture, 'winner' => $winner, 'score' => $score] = predictableFixture();
    $user = User::factory()->create();

    actingAs($user)->post(route('predict.store'), [
        'date' => $fixture->watDate(),
        'predictions' => [
            ['fixture_market_id' => $winner->id, 'value' => ['selected' => 'home']],
            ['fixture_market_id' => $score->id, 'value' => ['home' => 2, 'away' => 1]],
        ],
        'banker_fixture_market_id' => $winner->id,
    ])->assertRedirect();

    $winnerPick = Prediction::query()->where('fixture_market_id', $winner->id)->sole();

    expect(Prediction::query()->where('user_id', $user->id)->count())->toBe(2)
        ->and($winnerPick->value)->toBe(['selected' => 'home'])
        ->and($winnerPick->is_banker)->toBeTrue();
});

it('keeps at most one banker per day', function () {
    ['fixture' => $fixture, 'winner' => $winner, 'score' => $score] = predictableFixture();
    $user = User::factory()->create();

    $submit = fn (int $banker) => actingAs($user)->post(route('predict.store'), [
        'date' => $fixture->watDate(),
        'predictions' => [
            ['fixture_market_id' => $winner->id, 'value' => ['selected' => 'home']],
            ['fixture_market_id' => $score->id, 'value' => ['home' => 1, 'away' => 0]],
        ],
        'banker_fixture_market_id' => $banker,
    ]);

    $submit($winner->id)->assertRedirect();
    $submit($score->id)->assertRedirect();

    expect(Prediction::query()->where('user_id', $user->id)->where('is_banker', true)->count())->toBe(1)
        ->and(Prediction::query()->where('fixture_market_id', $score->id)->sole()->is_banker)->toBeTrue()
        ->and(Prediction::query()->where('fixture_market_id', $winner->id)->sole()->is_banker)->toBeFalse();
});

it('rejects predictions on a locked market', function () {
    $kickoff = now()->addMinutes(30); // lock_at (kickoff − 1h) is already in the past.

    $fixture = Fixture::factory()->create([
        'home_team_id' => Team::factory()->create()->id,
        'away_team_id' => Team::factory()->create()->id,
        'kickoff_at' => $kickoff,
        'lock_at' => $kickoff->copy()->subHour(),
    ]);

    $market = FixtureMarket::factory()->create([
        'fixture_id' => $fixture->id,
        'prediction_market_id' => PredictionMarket::factory()->matchWinner()->create()->id,
    ]);

    actingAs(User::factory()->create())->post(route('predict.store'), [
        'date' => $fixture->watDate(),
        'predictions' => [
            ['fixture_market_id' => $market->id, 'value' => ['selected' => 'home']],
        ],
        'banker_fixture_market_id' => null,
    ])->assertSessionHasErrors("markets.{$market->id}");

    expect(Prediction::count())->toBe(0);
});

it('rejects an invalid single-select value', function () {
    ['fixture' => $fixture, 'winner' => $winner] = predictableFixture();

    actingAs(User::factory()->create())->post(route('predict.store'), [
        'date' => $fixture->watDate(),
        'predictions' => [
            ['fixture_market_id' => $winner->id, 'value' => ['selected' => 'banana']],
        ],
        'banker_fixture_market_id' => null,
    ])->assertSessionHasErrors("markets.{$winner->id}");

    expect(Prediction::count())->toBe(0);
});

it('rejects a banker on a pick the user did not make', function () {
    ['fixture' => $fixture, 'winner' => $winner, 'score' => $score] = predictableFixture();

    actingAs(User::factory()->create())->post(route('predict.store'), [
        'date' => $fixture->watDate(),
        'predictions' => [
            ['fixture_market_id' => $winner->id, 'value' => ['selected' => 'home']],
        ],
        'banker_fixture_market_id' => $score->id,
    ])->assertSessionHasErrors('banker');
});

it('does not list match days beyond tomorrow in wat', function () {
    ['fixture' => $nearFixture] = predictableFixture(daysFromToday: 1);
    ['fixture' => $farFixture] = predictableFixture(daysFromToday: 3);

    actingAs(User::factory()->create())
        ->get(route('predict'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('predict')
            ->where('dates', fn ($dates) => in_array($nearFixture->watDate(), collect($dates)->all(), true)
                && ! in_array($farFixture->watDate(), collect($dates)->all(), true))
        );
});

it('falls back when requesting a match day that is not visible yet', function () {
    ['fixture' => $farFixture] = predictableFixture(daysFromToday: 5);
    ['fixture' => $nearFixture] = predictableFixture(daysFromToday: 1);

    actingAs(User::factory()->create())
        ->get(route('predict', ['date' => $farFixture->watDate()]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('predict')
            ->where('selectedDate', $nearFixture->watDate())
            ->has('fixtures', 1)
        );
});

it('rejects predictions for a match day that is not visible yet', function () {
    ['fixture' => $fixture, 'winner' => $winner] = predictableFixture(daysFromToday: 4);

    actingAs(User::factory()->create())->post(route('predict.store'), [
        'date' => $fixture->watDate(),
        'predictions' => [
            ['fixture_market_id' => $winner->id, 'value' => ['selected' => 'home']],
        ],
        'banker_fixture_market_id' => null,
    ])->assertSessionHasErrors('date');

    expect(Prediction::count())->toBe(0);
});
