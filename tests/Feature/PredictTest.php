<?php

use App\Enums\FixtureStatus;
use App\Models\Fixture;
use App\Models\FixtureMarket;
use App\Models\Prediction;
use App\Models\PredictionMarket;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it('allows guests to browse the predict page', function () {
    $fixture = predictableFixture()['fixture'];

    $this->get(route('predict', ['date' => $fixture->watDate()]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('predict')
            ->where('selectedDate', $fixture->watDate())
            ->has('fixtures', 1)
        );
});

it('requires authentication to submit predictions', function () {
    ['fixture' => $fixture, 'winner' => $winner] = predictableFixture();

    $this->post(route('predict.store'), [
        'date' => $fixture->watDate(),
        'predictions' => [
            ['fixture_market_id' => $winner->id, 'value' => ['selected' => 'home']],
        ],
        'banker_fixture_market_id' => null,
    ])->assertRedirect(route('login'));
});

it('remembers the predict return url for post-login redirect', function () {
    $this->post(route('predict.return-url'), [
        'return_url' => '/predict?date=2026-06-18',
    ])->assertNoContent();

    expect(session('url.intended'))->toBe('/predict?date=2026-06-18')
        ->and(session('predict_auth_flow'))->toBeTrue();
});

it('allows login from the predict flow without turnstile when enabled', function () {
    config([
        'turnstile.enabled' => true,
        'turnstile.site_key' => 'test-site-key',
        'turnstile.secret_key' => 'test-secret-key',
    ]);

    $user = User::factory()->create();

    $this->post(route('predict.return-url'), [
        'return_url' => '/predict',
    ])->assertNoContent();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('predict', absolute: false));

    $this->assertAuthenticated();
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

it('labels match winner options as home draw and away', function () {
    $fixture = predictableFixture()['fixture'];

    $this->get(route('predict', ['date' => $fixture->watDate()]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('fixtures.0.markets.0.options', [
                ['value' => 'home', 'label' => 'Home'],
                ['value' => 'draw', 'label' => 'Draw'],
                ['value' => 'away', 'label' => 'Away'],
            ])
        );
});

it('shows final scores on finished fixtures', function () {
    ['fixture' => $fixture] = predictableFixture();
    $fixture->update([
        'status' => FixtureStatus::Final,
        'home_score' => 2,
        'away_score' => 1,
    ]);

    actingAs(User::factory()->create())
        ->get(route('predict', ['date' => $fixture->watDate()]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('fixtures.0.status', 'final')
            ->where('fixtures.0.homeScore', 2)
            ->where('fixtures.0.awayScore', 1)
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
        ->and($winnerPick->is_banker)->toBeTrue()
        ->and($winnerPick->submitted_at)->not->toBeNull();
});

it('updates submitted_at when a pick is edited before lock', function () {
    ['fixture' => $fixture, 'winner' => $winner] = predictableFixture();
    $user = User::factory()->create();

    $payload = fn (string $selection) => [
        'date' => $fixture->watDate(),
        'predictions' => [
            ['fixture_market_id' => $winner->id, 'value' => ['selected' => $selection]],
        ],
        'banker_fixture_market_id' => null,
    ];

    actingAs($user)->post(route('predict.store'), $payload('home'))->assertRedirect();

    $originalSubmittedAt = Prediction::query()->where('fixture_market_id', $winner->id)->sole()->submitted_at;

    $this->travel(2)->hours();

    actingAs($user)->post(route('predict.store'), $payload('away'))->assertRedirect();

    $pick = Prediction::query()->where('fixture_market_id', $winner->id)->sole();

    expect($pick->submitted_at->greaterThan($originalSubmittedAt))->toBeTrue()
        ->and($pick->value)->toBe(['selected' => 'away']);
});

it('refreshes submitted_at on all day picks when any pick changes', function () {
    ['fixture' => $fixture, 'winner' => $winner, 'score' => $score] = predictableFixture();
    $user = User::factory()->create();

    actingAs($user)->post(route('predict.store'), [
        'date' => $fixture->watDate(),
        'predictions' => [
            ['fixture_market_id' => $winner->id, 'value' => ['selected' => 'home']],
            ['fixture_market_id' => $score->id, 'value' => ['home' => 2, 'away' => 1]],
        ],
        'banker_fixture_market_id' => null,
    ])->assertRedirect();

    $winnerSubmittedAt = Prediction::query()->where('fixture_market_id', $winner->id)->sole()->submitted_at;
    $scoreSubmittedAt = Prediction::query()->where('fixture_market_id', $score->id)->sole()->submitted_at;

    $this->travel(2)->hours();

    actingAs($user)->post(route('predict.store'), [
        'date' => $fixture->watDate(),
        'predictions' => [
            ['fixture_market_id' => $winner->id, 'value' => ['selected' => 'home']],
            ['fixture_market_id' => $score->id, 'value' => ['home' => 3, 'away' => 1]],
        ],
        'banker_fixture_market_id' => null,
    ])->assertRedirect();

    $winnerPick = Prediction::query()->where('fixture_market_id', $winner->id)->sole();
    $scorePick = Prediction::query()->where('fixture_market_id', $score->id)->sole();

    expect($winnerPick->submitted_at->equalTo($scorePick->submitted_at))->toBeTrue()
        ->and($winnerPick->submitted_at->greaterThan($winnerSubmittedAt))->toBeTrue()
        ->and($scorePick->submitted_at->greaterThan($scoreSubmittedAt))->toBeTrue();
});

it('does not refresh submitted_at when picks are unchanged', function () {
    ['fixture' => $fixture, 'winner' => $winner, 'score' => $score] = predictableFixture();
    $user = User::factory()->create();

    $payload = [
        'date' => $fixture->watDate(),
        'predictions' => [
            ['fixture_market_id' => $winner->id, 'value' => ['selected' => 'home']],
            ['fixture_market_id' => $score->id, 'value' => ['home' => 2, 'away' => 1]],
        ],
        'banker_fixture_market_id' => null,
    ];

    actingAs($user)->post(route('predict.store'), $payload)->assertRedirect();

    $originalSubmittedAt = Carbon::parse(Prediction::query()
        ->where('user_id', $user->id)
        ->min('submitted_at'));

    $this->travel(2)->hours();

    actingAs($user)->post(route('predict.store'), $payload)->assertRedirect();

    expect(Carbon::parse(Prediction::query()
        ->where('user_id', $user->id)
        ->min('submitted_at'))
        ->equalTo($originalSubmittedAt))->toBeTrue();
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

it('defaults to today in wat when no date is requested', function () {
    ['fixture' => $todayFixture] = predictableFixture(daysFromToday: 0);
    predictableFixture(daysFromToday: 1);

    actingAs(User::factory()->create())
        ->get(route('predict'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('selectedDate', $todayFixture->watDate())
        );
});

it('falls back to today when requesting a match day that is not visible yet', function () {
    ['fixture' => $todayFixture] = predictableFixture(daysFromToday: 0);
    ['fixture' => $farFixture] = predictableFixture(daysFromToday: 5);

    actingAs(User::factory()->create())
        ->get(route('predict', ['date' => $farFixture->watDate()]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('predict')
            ->where('selectedDate', $todayFixture->watDate())
            ->has('fixtures', 1)
        );
});

it('falls back to the nearest visible day when today has no fixtures', function () {
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
