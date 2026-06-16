<?php

namespace App\Predictions\Leaderboard;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LeaderboardService
{
    /**
     * Cumulative tournament standings: total points per user, ranked.
     *
     * @return Collection<int, array{userId: int, name: string, points: int, rank: int}>
     */
    public function overall(int $limit = 50): Collection
    {
        $rows = DB::table('predictions')
            ->join('users', 'users.id', '=', 'predictions.user_id')
            ->whereNotNull('predictions.points_awarded')
            ->groupBy('users.id', 'users.name')
            ->select('users.id', 'users.name', DB::raw('SUM(predictions.points_awarded) as points'), DB::raw('MIN(predictions.created_at) as first_at'))
            ->orderByDesc('points')
            ->orderBy('first_at')
            ->limit($limit)
            ->get();

        return $this->rank($rows);
    }

    /**
     * Standings for a single WAT match day: points per user for predictions
     * on fixtures kicking off that day, ranked.
     *
     * @return Collection<int, array{userId: int, name: string, points: int, rank: int}>
     */
    public function daily(string $watDate, int $limit = 50): Collection
    {
        [$start, $end] = $this->watDayWindow($watDate);

        $rows = DB::table('predictions')
            ->join('fixture_markets', 'fixture_markets.id', '=', 'predictions.fixture_market_id')
            ->join('fixtures', 'fixtures.id', '=', 'fixture_markets.fixture_id')
            ->join('users', 'users.id', '=', 'predictions.user_id')
            ->whereNotNull('predictions.points_awarded')
            ->whereBetween('fixtures.kickoff_at', [$start, $end])
            ->groupBy('users.id', 'users.name')
            ->select('users.id', 'users.name', DB::raw('SUM(predictions.points_awarded) as points'), DB::raw('MIN(predictions.created_at) as first_at'))
            ->orderByDesc('points')
            ->orderBy('first_at')
            ->limit($limit)
            ->get();

        return $this->rank($rows);
    }

    /**
     * @param  Collection<int, \stdClass>  $rows
     * @return Collection<int, array{userId: int, name: string, points: int, rank: int}>
     */
    private function rank(Collection $rows): Collection
    {
        return $rows->values()->map(function (\stdClass $row, int $index): array {
            $data = (array) $row;

            return [
                'userId' => (int) $data['id'],
                'name' => (string) $data['name'],
                'points' => (int) $data['points'],
                'rank' => $index + 1,
            ];
        });
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function watDayWindow(string $watDate): array
    {
        $start = Carbon::parse($watDate, config('predictions.timezone'))->startOfDay();

        return [$start->copy()->utc(), $start->copy()->addDay()->utc()];
    }
}
