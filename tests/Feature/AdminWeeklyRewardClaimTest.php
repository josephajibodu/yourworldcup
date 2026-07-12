<?php

use App\Enums\RewardPreference;
use App\Models\User;
use App\Models\WeeklyRewardClaim;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected from admin reward claims index', function () {
    $this->get(route('admin.reward-claims.index'))
        ->assertRedirect(route('login'));
});

test('non-admin users cannot access admin reward claims', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.reward-claims.index'))
        ->assertForbidden();
});

test('site admins can view weekly reward claim submissions', function () {
    $user = User::factory()->create(['name' => '@weekly_rank1', 'email' => 'weekly-rank1@example.test']);
    $weekStart = now(config('predictions.timezone'))->startOfWeek()->toDateString();

    WeeklyRewardClaim::factory()->create([
        'week_start' => $weekStart,
        'user_id' => $user->id,
        'leaderboard_rank' => 1,
        'preference' => RewardPreference::Airtime,
        'phone_number' => '08012345678',
        'mobile_network' => 'mtn',
    ]);

    WeeklyRewardClaim::factory()->passedOn('enjoy!')->create([
        'week_start' => $weekStart,
        'user_id' => User::factory()->create(['name' => '@weekly_rank2']),
        'leaderboard_rank' => 2,
    ]);

    $this->actingAs(siteAdmin())
        ->get(route('admin.reward-claims.index', ['week' => $weekStart]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/reward-claims/index')
            ->where('selectedWeek', $weekStart)
            ->where('summary.total', 2)
            ->where('summary.claimed', 1)
            ->where('summary.passed', 1)
            ->has('claims.data', 2)
            ->where('claims.data.0.user.email', 'weekly-rank1@example.test')
            ->where('claims.data.0.phoneNumber', '08012345678')
            ->where('claims.data.0.preferenceLabel', 'Airtime'));
});
