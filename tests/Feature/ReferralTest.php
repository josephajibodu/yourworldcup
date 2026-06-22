<?php

use App\Models\Prediction;
use App\Models\Referral;
use App\Models\User;
use App\Predictions\Leaderboard\LeaderboardService;
use App\Predictions\Settlement\SettlementService;
use App\Referrals\ReferralService;

use function Pest\Laravel\actingAs;

it('generates a unique referral code for each user', function () {
    $first = User::factory()->create();
    $second = User::factory()->create();

    expect($first->referral_code)->not->toBeNull()
        ->and($second->referral_code)->not->toBeNull()
        ->and($first->referral_code)->not->toBe($second->referral_code);
});

it('stores a referral code from the query string in session', function () {
    $referrer = User::factory()->create();

    $this->get('/?ref='.$referrer->referral_code)
        ->assertOk()
        ->assertSessionHas('referral_code', $referrer->referral_code);
});

it('links a new user to their referrer at registration', function () {
    $referrer = User::factory()->create();

    $this->get('/register?ref='.$referrer->referral_code);

    $this->post(route('register.store'), [
        'name' => 'Referred User',
        'email' => 'referred@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect(route('predict', absolute: false));

    $referred = User::query()->where('email', 'referred@example.com')->first();

    expect($referred)->not->toBeNull()
        ->and($referred->referred_by_id)->toBe($referrer->id);
});

it('does not allow retroactive referral assignment after registration', function () {
    $referrer = User::factory()->create();
    $referred = User::factory()->create(['referred_by_id' => null]);

    $this->get('/?ref='.$referrer->referral_code);

    expect($referred->fresh()->referred_by_id)->toBeNull();
});

it('credits the referrer when the referred user makes their first prediction', function () {
    ['fixture' => $fixture, 'winner' => $winner] = predictableFixture();
    $referrer = User::factory()->create();
    Prediction::factory()->for($referrer)->create(['fixture_market_id' => $winner->id]);

    $referred = User::factory()->create(['referred_by_id' => $referrer->id]);

    expect(Referral::query()->count())->toBe(0);

    actingAs($referred)->post(route('predict.store'), [
        'date' => $fixture->watDate(),
        'predictions' => [
            ['fixture_market_id' => $winner->id, 'value' => ['selected' => 'home']],
        ],
    ])->assertRedirect();

    expect(Referral::query()->count())->toBe(1)
        ->and(Referral::query()->first())->toMatchArray([
            'referrer_id' => $referrer->id,
            'referred_id' => $referred->id,
            'points' => 1,
        ]);
});

it('credits the referrer when they make their first prediction after the referred user has already predicted', function () {
    ['fixture' => $fixture, 'winner' => $winner] = predictableFixture();
    $referrer = User::factory()->create();
    $referred = User::factory()->create(['referred_by_id' => $referrer->id]);

    actingAs($referred)->post(route('predict.store'), [
        'date' => $fixture->watDate(),
        'predictions' => [
            ['fixture_market_id' => $winner->id, 'value' => ['selected' => 'away']],
        ],
    ])->assertRedirect();

    expect(Referral::query()->count())->toBe(0);

    actingAs($referrer)->post(route('predict.store'), [
        'date' => $fixture->watDate(),
        'predictions' => [
            ['fixture_market_id' => $winner->id, 'value' => ['selected' => 'home']],
        ],
    ])->assertRedirect();

    expect(Referral::query()->count())->toBe(1)
        ->and(Referral::query()->first()->referrer_id)->toBe($referrer->id);
});

it('does not credit when the referrer has not made a prediction', function () {
    ['fixture' => $fixture, 'winner' => $winner] = predictableFixture();
    $referrer = User::factory()->create();
    $referred = User::factory()->create(['referred_by_id' => $referrer->id]);

    actingAs($referred)->post(route('predict.store'), [
        'date' => $fixture->watDate(),
        'predictions' => [
            ['fixture_market_id' => $winner->id, 'value' => ['selected' => 'home']],
        ],
    ])->assertRedirect();

    expect(Referral::query()->count())->toBe(0);
});

it('does not credit when the referred user has not made a prediction', function () {
    ['winner' => $winner] = predictableFixture();
    $referrer = User::factory()->create();
    Prediction::factory()->for($referrer)->create(['fixture_market_id' => $winner->id]);
    $referred = User::factory()->create(['referred_by_id' => $referrer->id]);

    app(ReferralService::class)->attemptCredit($referred);

    expect(Referral::query()->count())->toBe(0);
});

it('enforces the daily referral cap', function () {
    ['fixture' => $fixture, 'winner' => $winner] = predictableFixture();
    $referrer = User::factory()->create();
    Prediction::factory()->for($referrer)->create(['fixture_market_id' => $winner->id]);

    for ($i = 0; $i < 6; $i++) {
        $referred = User::factory()->create(['referred_by_id' => $referrer->id]);

        actingAs($referred)->post(route('predict.store'), [
            'date' => $fixture->watDate(),
            'predictions' => [
                ['fixture_market_id' => $winner->id, 'value' => ['selected' => 'home']],
            ],
        ])->assertRedirect();
    }

    expect(Referral::query()->where('referrer_id', $referrer->id)->count())->toBe(5);
});

it('only credits a referred user once', function () {
    ['fixture' => $fixture, 'winner' => $winner] = predictableFixture();
    $referrer = User::factory()->create();
    Prediction::factory()->for($referrer)->create(['fixture_market_id' => $winner->id]);
    $referred = User::factory()->create(['referred_by_id' => $referrer->id]);

    $submit = fn () => actingAs($referred)->post(route('predict.store'), [
        'date' => $fixture->watDate(),
        'predictions' => [
            ['fixture_market_id' => $winner->id, 'value' => ['selected' => 'home']],
        ],
    ])->assertRedirect();

    $submit();
    $submit();

    expect(Referral::query()->where('referred_id', $referred->id)->count())->toBe(1);
});

it('adds referral points to the overall leaderboard', function () {
    ['fixture' => $fixture, 'winner' => $winner] = finalFixtureWithMarkets(1, 0);
    $referrer = User::factory()->create(['name' => 'Referrer']);
    $referred = User::factory()->create(['referred_by_id' => $referrer->id]);

    Prediction::factory()->for($referrer)->create([
        'fixture_market_id' => $winner->id,
        'value' => ['selected' => 'away'],
    ]);

    Referral::factory()->create([
        'referrer_id' => $referrer->id,
        'referred_id' => $referred->id,
        'points' => 1,
        'wat_date' => $fixture->watDate(),
    ]);

    $ada = User::factory()->create(['name' => 'Ada']);
    Prediction::factory()->for($ada)->create([
        'fixture_market_id' => $winner->id,
        'value' => ['selected' => 'home'],
    ]);

    app(SettlementService::class)->settleFixture($fixture);

    $overall = app(LeaderboardService::class)->overall();

    expect($overall)->toHaveCount(2)
        ->and($overall->first())->toMatchArray(['name' => 'Referrer', 'points' => 1, 'rank' => 1])
        ->and($overall->last())->toMatchArray(['name' => 'Ada', 'points' => 1, 'rank' => 2]);
});

it('adds referral points to the weekly leaderboard for the credit week', function () {
    ['fixture' => $fixture] = finalFixtureWithMarkets(1, 0);
    $referrer = User::factory()->create(['name' => 'Referrer']);
    $referred = User::factory()->create(['referred_by_id' => $referrer->id]);
    $weekStart = watWeekStart($fixture->watDate());

    Referral::factory()->create([
        'referrer_id' => $referrer->id,
        'referred_id' => $referred->id,
        'points' => 1,
        'wat_date' => $fixture->watDate(),
    ]);

    $service = app(LeaderboardService::class);

    expect($service->weekly($weekStart))->toHaveCount(1)
        ->and($service->weekly('2026-01-05'))->toHaveCount(0);
});
