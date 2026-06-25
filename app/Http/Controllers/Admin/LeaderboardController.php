<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Predictions\Leaderboard\LeaderboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class LeaderboardController extends Controller
{
    public function overall(LeaderboardService $leaderboard): Response
    {
        return Inertia::render('admin/leaderboard/overall', [
            'standings' => $leaderboard->overall(),
        ]);
    }

    public function weekly(Request $request, LeaderboardService $leaderboard): Response
    {
        $weeks = $leaderboard->weeklyDates();
        $selected = $this->selectedDate($request->query('date'), $weeks);

        return Inertia::render('admin/leaderboard/weekly', [
            'dates' => $weeks,
            'selectedDate' => $selected,
            'standings' => $selected === null ? [] : $leaderboard->weekly($selected),
        ]);
    }

    public function daily(Request $request, LeaderboardService $leaderboard): Response
    {
        $dates = $leaderboard->dailyDates();
        $selected = $this->selectedDailyDate($request->query('date'), $dates);

        return Inertia::render('admin/leaderboard/daily', [
            'dates' => $dates,
            'selectedDate' => $selected,
            'dateRange' => [
                'start' => config('predictions.tournament_start'),
                'end' => config('predictions.tournament_end'),
            ],
            'standings' => $selected === null ? [] : $leaderboard->daily($selected),
        ]);
    }

    /**
     * @param  array<int, string>  $dates
     */
    private function selectedDailyDate(?string $requested, array $dates): ?string
    {
        if ($requested !== null && in_array($requested, $dates, true)) {
            return $requested;
        }

        $today = Carbon::now(config('predictions.timezone'))->toDateString();

        if (in_array($today, $dates, true)) {
            return $today;
        }

        $pastOrToday = array_values(array_filter(
            $dates,
            static fn (string $date): bool => $date <= $today,
        ));

        if ($pastOrToday !== []) {
            return end($pastOrToday);
        }

        return $dates === [] ? null : end($dates);
    }

    /**
     * @param  array<int, string>  $dates
     */
    private function selectedDate(?string $requested, array $dates): ?string
    {
        if ($requested !== null && in_array($requested, $dates, true)) {
            return $requested;
        }

        return $dates === [] ? null : end($dates);
    }
}
