<?php

use App\Enums\RewardPreference;
use App\Models\User;
use App\Models\WeeklyRewardClaim;
use App\Rewards\WeeklyRewardService;
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

test('site admins can see players awaiting a reward response', function () {
    ['weekStart' => $weekStart, 'fixture' => $fixture, 'winner' => $winner] = weeklySundayFixture();
    $users = User::factory()->count(3)->create();

    rankUsersForWeek($weekStart, $fixture, $winner, $users);

    WeeklyRewardClaim::factory()->passedOn()->create([
        'week_start' => $weekStart,
        'user_id' => $users[0]->id,
        'leaderboard_rank' => 1,
    ]);

    $this->actingAs(siteAdmin())
        ->get(route('admin.reward-claims.index', ['week' => $weekStart]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('pendingPassOns', [
                [
                    'userId' => $users[1]->id,
                    'rank' => 2,
                    'name' => $users[1]->name,
                    'email' => $users[1]->email,
                ],
                [
                    'userId' => $users[2]->id,
                    'rank' => 3,
                    'name' => $users[2]->name,
                    'email' => $users[2]->email,
                ],
            ]));
});

test('site admins can pass on a weekly reward for a player', function () {
    ['weekStart' => $weekStart, 'fixture' => $fixture, 'winner' => $winner] = weeklySundayFixture();
    $users = User::factory()->count(4)->create();

    rankUsersForWeek($weekStart, $fixture, $winner, $users);

    $this->actingAs(siteAdmin())
        ->post(route('admin.reward-claims.pass-on', $users[0]), [
            'week_start' => $weekStart,
            'pass_on_message' => 'Passed on by admin',
        ])
        ->assertRedirect()
        ->assertSessionHas('rewardPassOnRecorded', true);

    $claim = WeeklyRewardClaim::query()->sole();

    expect($claim->user_id)->toBe($users[0]->id)
        ->and($claim->passed_on)->toBeTrue()
        ->and($claim->pass_on_message)->toBe('Passed on by admin')
        ->and($claim->leaderboard_rank)->toBe(1)
        ->and(app(WeeklyRewardService::class)->isUserEligible($users[3], $weekStart))->toBeTrue();
});

test('non-admin users cannot pass on weekly rewards', function () {
    ['weekStart' => $weekStart, 'fixture' => $fixture, 'winner' => $winner] = weeklySundayFixture();
    $users = User::factory()->count(2)->create();

    rankUsersForWeek($weekStart, $fixture, $winner, $users);

    $this->actingAs(User::factory()->create())
        ->post(route('admin.reward-claims.pass-on', $users[0]), [
            'week_start' => $weekStart,
        ])
        ->assertForbidden();
});

test('admin pass-on rejects players who already responded', function () {
    ['weekStart' => $weekStart, 'fixture' => $fixture, 'winner' => $winner] = weeklySundayFixture();
    $users = User::factory()->count(2)->create();

    rankUsersForWeek($weekStart, $fixture, $winner, $users);

    WeeklyRewardClaim::factory()->create([
        'week_start' => $weekStart,
        'user_id' => $users[0]->id,
        'leaderboard_rank' => 1,
        'preference' => RewardPreference::Airtime,
        'phone_number' => '08012345678',
        'mobile_network' => 'mtn',
    ]);

    $this->actingAs(siteAdmin())
        ->post(route('admin.reward-claims.pass-on', $users[0]), [
            'week_start' => $weekStart,
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('week_start');
});
