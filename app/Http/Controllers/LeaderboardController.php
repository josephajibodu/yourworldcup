<?php

namespace App\Http\Controllers;

use App\Predictions\Leaderboard\LeaderboardService;
use App\Rewards\WeeklyRewardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeaderboardController extends Controller
{
    public function index(
        Request $request,
        LeaderboardService $leaderboard,
        WeeklyRewardService $rewards,
    ): Response {
        $weeks = $leaderboard->weeklyDates();
        $selected = $this->selectedDate($request->query('date'), $weeks, $rewards);

        return Inertia::render('leaderboard', [
            'overall' => $leaderboard->overall(),
            'dates' => $weeks,
            'selectedDate' => $selected,
            'weekly' => $selected === null ? [] : $leaderboard->weekly($selected),
            'weeklyReward' => $selected === null
                ? null
                : $rewards->statusForUser($request->user(), $selected),
        ]);
    }

    /**
     * @param  array<int, string>  $dates
     */
    private function selectedDate(?string $requested, array $dates, WeeklyRewardService $rewards): ?string
    {
        if ($requested !== null && in_array($requested, $dates, true)) {
            return $requested;
        }

        if ($dates === []) {
            return null;
        }

        $openWeek = $rewards->openClaimWeekStart();

        if ($openWeek !== null && in_array($openWeek, $dates, true)) {
            return $openWeek;
        }

        return end($dates);
    }
}
