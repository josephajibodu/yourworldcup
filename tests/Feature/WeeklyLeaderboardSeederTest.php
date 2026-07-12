<?php

use App\Predictions\Leaderboard\LeaderboardService;
use App\Rewards\WeeklyRewardService;
use Database\Seeders\WeeklyLeaderboardSeeder;
use Illuminate\Support\Carbon;

it('seeds a ranked weekly leaderboard for the current wat week', function () {
    $this->seed(WeeklyLeaderboardSeeder::class);

    $weekStart = Carbon::now(config('predictions.timezone'))
        ->startOfWeek(Carbon::MONDAY)
        ->toDateString();

    $weekly = app(LeaderboardService::class)->weekly($weekStart);

    expect($weekly->take(5)->pluck('name', 'rank')->all())->toBe([
        1 => '@weekly_rank1',
        2 => '@weekly_rank2',
        3 => '@weekly_rank3',
        4 => '@weekly_rank4',
        5 => '@weekly_rank5',
    ])->and($weekly[0]['points'])->toBe(5)
        ->and($weekly[1]['points'])->toBe(4)
        ->and($weekly[2]['points'])->toBe(2)
        ->and(app(WeeklyRewardService::class)->isWeekReadyForClaims($weekStart))->toBeTrue();
});

it('can be re-run without duplicating seeded predictions', function () {
    $this->seed(WeeklyLeaderboardSeeder::class);
    $this->seed(WeeklyLeaderboardSeeder::class);

    $weekStart = Carbon::now(config('predictions.timezone'))
        ->startOfWeek(Carbon::MONDAY)
        ->toDateString();

    expect(app(LeaderboardService::class)->weekly($weekStart))->toHaveCount(5);
});
