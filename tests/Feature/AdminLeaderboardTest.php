<?php

use App\Models\Prediction;
use App\Models\Referral;
use App\Models\User;
use App\Predictions\Leaderboard\LeaderboardService;
use App\Predictions\Settlement\SettlementService;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected from admin leaderboard', function () {
    $this->get(route('admin.leaderboard'))
        ->assertRedirect(route('login'));
});

test('non-admin users cannot access admin leaderboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.leaderboard'))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('admin.leaderboard.weekly'))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('admin.leaderboard.daily'))
        ->assertForbidden();
});

test('site admins can view the overall leaderboard', function () {
    ['fixture' => $fixture, 'winner' => $winner] = finalFixtureWithMarkets(1, 0);
    Prediction::factory()->for(User::factory()->create(['name' => 'Ada']))->create([
        'fixture_market_id' => $winner->id,
        'value' => ['selected' => 'home'],
    ]);
    app(SettlementService::class)->settleFixture($fixture);

    $this->actingAs(siteAdmin())
        ->get(route('admin.leaderboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/leaderboard/overall')
            ->has('standings', 1)
            ->where('standings.0.name', 'Ada')
        );
});

test('site admins can view the weekly leaderboard', function () {
    ['fixture' => $fixture, 'winner' => $winner] = finalFixtureWithMarkets(1, 0);
    Prediction::factory()->for(User::factory()->create(['name' => 'Ada']))->create([
        'fixture_market_id' => $winner->id,
        'value' => ['selected' => 'home'],
    ]);
    app(SettlementService::class)->settleFixture($fixture);

    $weekStart = watWeekStart($fixture->watDate());

    $this->actingAs(siteAdmin())
        ->get(route('admin.leaderboard.weekly', ['date' => $weekStart]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/leaderboard/weekly')
            ->where('selectedDate', $weekStart)
            ->has('standings', 1)
            ->where('standings.0.name', 'Ada')
        );
});

test('site admins can view the daily leaderboard with referral points', function () {
    ['fixture' => $fixture, 'winner' => $winner] = finalFixtureWithMarkets(1, 0);
    $referrer = User::factory()->create(['name' => 'Referrer']);
    $referred = User::factory()->create(['referred_by_id' => $referrer->id]);
    $watDate = $fixture->watDate();

    Referral::factory()->create([
        'referrer_id' => $referrer->id,
        'referred_id' => $referred->id,
        'points' => 1,
        'wat_date' => $watDate,
    ]);

    $ada = User::factory()->create(['name' => 'Ada']);
    Prediction::factory()->for($ada)->create([
        'fixture_market_id' => $winner->id,
        'value' => ['selected' => 'home'],
    ]);

    app(SettlementService::class)->settleFixture($fixture);

    $this->actingAs(siteAdmin())
        ->get(route('admin.leaderboard.daily', ['date' => $watDate]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/leaderboard/daily')
            ->where('selectedDate', $watDate)
            ->has('standings', 2)
        );

    $daily = app(LeaderboardService::class)->daily($watDate);

    expect($daily)->toHaveCount(2)
        ->and($daily->pluck('name')->all())->toEqualCanonicalizing(['Ada', 'Referrer'])
        ->and($daily->sum('points'))->toBe(2);
});

test('admin daily leaderboard exposes tournament days for navigation', function () {
    $dates = app(LeaderboardService::class)->dailyDates();

    expect($dates[0])->toBe('2026-06-11')
        ->and(end($dates))->toBe('2026-07-19')
        ->and(count($dates))->toBe(39);

    $this->actingAs(siteAdmin())
        ->get(route('admin.leaderboard.daily', ['date' => '2026-06-15']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/leaderboard/daily')
            ->where('selectedDate', '2026-06-15')
            ->where('dateRange.start', '2026-06-11')
            ->where('dateRange.end', '2026-07-19')
            ->where('dates', $dates)
        );
});
