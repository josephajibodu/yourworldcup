<?php

use App\Enums\FixtureStatus;
use App\Enums\MobileNetwork;
use App\Enums\RewardPreference;
use App\Models\Fixture;
use App\Models\FixtureMarket;
use App\Models\Prediction;
use App\Models\Team;
use App\Models\User;
use App\Models\WeeklyRewardClaim;
use App\Predictions\Leaderboard\LeaderboardService;
use App\Predictions\Settlement\SettlementService;
use App\Rewards\WeeklyRewardService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * @return array{weekStart: string, weekSunday: string, fixture: Fixture, winner: FixtureMarket}
 */
function weeklySundayFixture(int $home = 2, int $away = 1, ?string $weekStart = null): array
{
    $timezone = config('predictions.timezone');
    $weekStart ??= Carbon::now($timezone)->startOfWeek(Carbon::MONDAY)->toDateString();
    $weekSunday = Carbon::parse($weekStart, $timezone)->addDays(6)->toDateString();
    $kickoff = Carbon::parse("{$weekSunday} 21:00:00", $timezone)->utc();

    $fixture = Fixture::factory()->create([
        'home_team_id' => Team::factory()->create()->id,
        'away_team_id' => Team::factory()->create()->id,
        'kickoff_at' => $kickoff,
        'lock_at' => $kickoff->copy()->subHour(),
        'status' => FixtureStatus::Scheduled,
        'home_score' => null,
        'away_score' => null,
    ]);

    ['matchWinner' => $matchWinner] = predictionMarkets();

    $winner = FixtureMarket::factory()->create([
        'fixture_id' => $fixture->id,
        'prediction_market_id' => $matchWinner->id,
    ]);

    return compact('weekStart', 'weekSunday', 'fixture', 'winner');
}

function settleWeeklyFixture(Fixture $fixture, int $home, int $away): void
{
    $fixture->update([
        'status' => FixtureStatus::Final,
        'home_score' => $home,
        'away_score' => $away,
        'winner_team_id' => $home > $away ? $fixture->home_team_id : $fixture->away_team_id,
    ]);

    app(SettlementService::class)->settleFixture($fixture->fresh());
}

/**
 * @param  array<int, User>|Collection<int, User>  $users
 */
function rankUsersForWeek(string $weekStart, Fixture $fixture, $winnerMarket, array|Collection $users): void
{
    $users = collect($users);

    $points = $users->count();

    foreach ($users as $index => $user) {
        Prediction::factory()->for($user)->create([
            'fixture_market_id' => $winnerMarket->id,
            'value' => ['selected' => 'home'],
            'submitted_at' => now()->subHours($points - $index),
        ]);
    }

    settleWeeklyFixture($fixture, 2, 1);
}

it('opens weekly rewards after the last Sunday match is final', function () {
    ['weekStart' => $weekStart, 'fixture' => $fixture] = weeklySundayFixture();
    $service = app(WeeklyRewardService::class);

    expect($service->isWeekReadyForClaims($weekStart))->toBeFalse();

    settleWeeklyFixture($fixture, 2, 1);

    expect($service->isWeekReadyForClaims($weekStart))->toBeTrue();
});

it('considers the top three weekly ranks initially', function () {
    ['weekStart' => $weekStart, 'fixture' => $fixture, 'winner' => $winner] = weeklySundayFixture();
    $users = User::factory()->count(4)->create();

    rankUsersForWeek($weekStart, $fixture, $winner, $users);

    $service = app(WeeklyRewardService::class);

    expect($service->isUserEligible($users[0], $weekStart))->toBeTrue()
        ->and($service->isUserEligible($users[1], $weekStart))->toBeTrue()
        ->and($service->isUserEligible($users[2], $weekStart))->toBeTrue()
        ->and($service->isUserEligible($users[3], $weekStart))->toBeFalse();
});

it('extends consideration by one rank for each pass in order', function () {
    ['weekStart' => $weekStart, 'fixture' => $fixture, 'winner' => $winner] = weeklySundayFixture();
    $users = User::factory()->count(6)->create();

    rankUsersForWeek($weekStart, $fixture, $winner, $users);

    WeeklyRewardClaim::factory()->passedOn()->create([
        'week_start' => $weekStart,
        'user_id' => $users[0]->id,
        'leaderboard_rank' => 1,
    ]);

    WeeklyRewardClaim::factory()->passedOn()->create([
        'week_start' => $weekStart,
        'user_id' => $users[2]->id,
        'leaderboard_rank' => 3,
    ]);

    $service = app(WeeklyRewardService::class);

    expect($service->considerationCeiling($weekStart))->toBe(5)
        ->and($service->isUserEligible($users[3], $weekStart))->toBeTrue()
        ->and($service->isUserEligible($users[4], $weekStart))->toBeTrue()
        ->and($service->isUserEligible($users[5], $weekStart))->toBeFalse();
});

it('does not skip ranks when the first and third players pass', function () {
    ['weekStart' => $weekStart, 'fixture' => $fixture, 'winner' => $winner] = weeklySundayFixture();
    $users = User::factory()->count(6)->create();

    rankUsersForWeek($weekStart, $fixture, $winner, $users);

    WeeklyRewardClaim::factory()->passedOn('from first')->create([
        'week_start' => $weekStart,
        'user_id' => $users[0]->id,
        'leaderboard_rank' => 1,
    ]);

    WeeklyRewardClaim::factory()->passedOn('from third')->create([
        'week_start' => $weekStart,
        'user_id' => $users[2]->id,
        'leaderboard_rank' => 3,
    ]);

    $fourth = $users[3];
    $fifth = $users[4];
    $statusForFourth = app(WeeklyRewardService::class)->statusForUser($fourth, $weekStart);
    $statusForFifth = app(WeeklyRewardService::class)->statusForUser($fifth, $weekStart);

    expect($statusForFourth['eligible'][0]['rank'])->toBe(4)
        ->and($statusForFourth['eligible'][0]['passedDown'])->toBeTrue()
        ->and($statusForFifth['eligible'][0]['rank'])->toBe(5)
        ->and($statusForFifth['eligible'][0]['passedDown'])->toBeTrue();
});

it('stores a weekly reward claim with the chosen preference', function () {
    ['weekStart' => $weekStart, 'fixture' => $fixture, 'winner' => $winner] = weeklySundayFixture();
    $first = User::factory()->create();

    rankUsersForWeek($weekStart, $fixture, $winner, [$first]);

    $claim = app(WeeklyRewardService::class)->submitClaim($first, [
        'week_start' => $weekStart,
        'passed_on' => false,
        'preference' => RewardPreference::Data->value,
        'phone_number' => '08012345678',
        'mobile_network' => 'mtn',
    ]);

    expect($claim->preference)->toBe(RewardPreference::Data)
        ->and($claim->passed_on)->toBeFalse()
        ->and($claim->leaderboard_rank)->toBe(1)
        ->and($claim->phone_number)->toBe('08012345678')
        ->and($claim->mobile_network)->toBe(MobileNetwork::Mtn);
});

it('stores bank details for a cash equivalent claim', function () {
    ['weekStart' => $weekStart, 'fixture' => $fixture, 'winner' => $winner] = weeklySundayFixture();
    $first = User::factory()->create();

    rankUsersForWeek($weekStart, $fixture, $winner, [$first]);

    $claim = app(WeeklyRewardService::class)->submitClaim($first, [
        'week_start' => $weekStart,
        'passed_on' => false,
        'preference' => RewardPreference::Cash->value,
        'account_holder_name' => 'Ada Lovelace',
        'bank_name' => 'GTBank',
        'account_number' => '0123456789',
    ]);

    expect($claim->preference)->toBe(RewardPreference::Cash)
        ->and($claim->account_holder_name)->toBe('Ada Lovelace')
        ->and($claim->bank_name)->toBe('GTBank')
        ->and($claim->account_number)->toBe('0123456789')
        ->and($claim->phone_number)->toBeNull();
});

it('requires phone and network details when claiming airtime', function () {
    ['weekStart' => $weekStart, 'fixture' => $fixture, 'winner' => $winner] = weeklySundayFixture();
    $first = User::factory()->create();

    rankUsersForWeek($weekStart, $fixture, $winner, [$first]);

    $this->actingAs($first)
        ->post(route('rewards.weekly-claim.store'), [
            'week_start' => $weekStart,
            'passed_on' => '0',
            'preference' => 'airtime',
        ])
        ->assertSessionHasErrors(['phone_number', 'mobile_network']);
});

it('requires bank details when claiming cash', function () {
    ['weekStart' => $weekStart, 'fixture' => $fixture, 'winner' => $winner] = weeklySundayFixture();
    $first = User::factory()->create();

    rankUsersForWeek($weekStart, $fixture, $winner, [$first]);

    $this->actingAs($first)
        ->post(route('rewards.weekly-claim.store'), [
            'week_start' => $weekStart,
            'passed_on' => '0',
            'preference' => 'cash',
        ])
        ->assertSessionHasErrors(['account_holder_name', 'bank_name', 'account_number']);
});

it('opens the next rank after a pass-on response', function () {
    ['weekStart' => $weekStart, 'fixture' => $fixture, 'winner' => $winner] = weeklySundayFixture();
    $users = User::factory()->count(4)->create();

    rankUsersForWeek($weekStart, $fixture, $winner, $users);

    app(WeeklyRewardService::class)->submitClaim($users[0], [
        'week_start' => $weekStart,
        'passed_on' => true,
        'pass_on_message' => 'Pay it forward',
    ]);

    expect(app(WeeklyRewardService::class)->isUserEligible($users[3], $weekStart))->toBeTrue();
});

it('rejects a second claim once all rewards are taken', function () {
    ['weekStart' => $weekStart, 'fixture' => $fixture, 'winner' => $winner] = weeklySundayFixture();
    $users = User::factory()->count(3)->create();

    rankUsersForWeek($weekStart, $fixture, $winner, $users);

    app(WeeklyRewardService::class)->submitClaim($users[0], [
        'week_start' => $weekStart,
        'passed_on' => false,
        'preference' => RewardPreference::Airtime->value,
        'phone_number' => '08011111111',
        'mobile_network' => 'mtn',
    ]);

    app(WeeklyRewardService::class)->submitClaim($users[1], [
        'week_start' => $weekStart,
        'passed_on' => false,
        'preference' => RewardPreference::Data->value,
        'phone_number' => '08022222222',
        'mobile_network' => 'airtel',
    ]);

    app(WeeklyRewardService::class)->submitClaim($users[2], [
        'week_start' => $weekStart,
        'passed_on' => false,
        'preference' => RewardPreference::Cash->value,
        'account_holder_name' => 'Third Player',
        'bank_name' => 'Access Bank',
        'account_number' => '0987654321',
    ]);

    expect(app(WeeklyRewardService::class)->isUserEligible($users[0], $weekStart))->toBeFalse();
});

it('rejects duplicate responses from the same user', function () {
    ['weekStart' => $weekStart, 'fixture' => $fixture, 'winner' => $winner] = weeklySundayFixture();
    $users = User::factory()->count(2)->create();

    rankUsersForWeek($weekStart, $fixture, $winner, $users);

    app(WeeklyRewardService::class)->submitClaim($users[0], [
        'week_start' => $weekStart,
        'passed_on' => false,
        'preference' => RewardPreference::Airtime->value,
        'phone_number' => '08011111111',
        'mobile_network' => 'mtn',
    ]);

    expect(fn () => app(WeeklyRewardService::class)->submitClaim($users[0], [
        'week_start' => $weekStart,
        'passed_on' => true,
    ]))->toThrow(ValidationException::class);
});

it('lets an authenticated top player submit a weekly reward claim', function () {
    ['weekStart' => $weekStart, 'fixture' => $fixture, 'winner' => $winner] = weeklySundayFixture();
    $first = User::factory()->create();

    rankUsersForWeek($weekStart, $fixture, $winner, [$first]);

    $this->actingAs($first)
        ->post(route('rewards.weekly-claim.store'), [
            'week_start' => $weekStart,
            'passed_on' => '0',
            'preference' => 'cash',
            'account_holder_name' => 'Ada Lovelace',
            'bank_name' => 'GTBank',
            'account_number' => '0123456789',
        ])
        ->assertRedirect()
        ->assertSessionHas('rewardClaimSubmitted', true);

    expect(WeeklyRewardClaim::query()->count())->toBe(1);
});

it('includes weekly reward eligibility on the leaderboard page', function () {
    Cache::flush();

    ['weekStart' => $weekStart, 'fixture' => $fixture, 'winner' => $winner] = weeklySundayFixture();
    $first = User::factory()->create();

    rankUsersForWeek($weekStart, $fixture, $winner, [$first]);

    $this->actingAs($first)
        ->get('/leaderboard?date='.$weekStart)
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('leaderboard')
            ->where('selectedDate', $weekStart)
            ->where('weeklyReward.ready', true)
            ->where('weeklyReward.eligible', [
                [
                    'rank' => 1,
                    'passedDown' => false,
                    'passedFromName' => null,
                    'passOnMessage' => null,
                ],
            ]));
});

it('bumps leaderboard cache after settlement so weekly ranks stay current', function () {
    ['weekStart' => $weekStart, 'fixture' => $fixture, 'winner' => $winner] = weeklySundayFixture();
    $first = User::factory()->create();

    Prediction::factory()->for($first)->create([
        'fixture_market_id' => $winner->id,
        'value' => ['selected' => 'home'],
    ]);

    settleWeeklyFixture($fixture, 2, 1);

    expect(app(LeaderboardService::class)->weekly($weekStart)->first())
        ->toMatchArray(['userId' => $first->id, 'rank' => 1, 'points' => 1]);
});
