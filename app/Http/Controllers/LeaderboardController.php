<?php

namespace App\Http\Controllers;

use App\Models\Fixture;
use App\Predictions\Leaderboard\LeaderboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeaderboardController extends Controller
{
    public function index(Request $request, LeaderboardService $leaderboard): Response
    {
        $dates = $this->scoredDates();
        $selected = $this->selectedDate($request->query('date'), $dates);

        return Inertia::render('leaderboard', [
            'overall' => $leaderboard->overall(),
            'dates' => $dates,
            'selectedDate' => $selected,
            'daily' => $selected === null ? [] : $leaderboard->daily($selected),
        ]);
    }

    /**
     * WAT match days that already have scored predictions, chronologically.
     *
     * @return array<int, string>
     */
    private function scoredDates(): array
    {
        return Fixture::query()
            ->whereHas('markets.predictions', fn ($query) => $query->whereNotNull('points_awarded'))
            ->orderBy('kickoff_at')
            ->get(['kickoff_at'])
            ->map(fn (Fixture $fixture): string => $fixture->watDate())
            ->unique()
            ->values()
            ->all();
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
