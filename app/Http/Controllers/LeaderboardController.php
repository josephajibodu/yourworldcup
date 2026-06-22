<?php

namespace App\Http\Controllers;

use App\Predictions\Leaderboard\LeaderboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeaderboardController extends Controller
{
    public function index(Request $request, LeaderboardService $leaderboard): Response
    {
        $weeks = $leaderboard->weeklyDates();
        $selected = $this->selectedDate($request->query('date'), $weeks);

        return Inertia::render('leaderboard', [
            'overall' => $leaderboard->overall(),
            'dates' => $weeks,
            'selectedDate' => $selected,
            'weekly' => $selected === null ? [] : $leaderboard->weekly($selected),
        ]);
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
