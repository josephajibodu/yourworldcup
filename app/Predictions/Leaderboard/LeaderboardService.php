<?php

namespace App\Predictions\Leaderboard;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LeaderboardService
{
    /**
     * Cumulative tournament standings: prediction + referral points, ranked.
     *
     * @return Collection<int, array{userId: int, name: string, points: int, rank: int}>
     */
    public function overall(int $limit = 50): Collection
    {
        $rows = $this->aggregateScores(
            predictionFilter: null,
            referralFilter: null,
            limit: $limit,
        );

        return $this->rank($rows);
    }

    /**
     * Standings for a single WAT match day: prediction points on that day's
     * fixtures plus referral credits earned that WAT day, ranked.
     *
     * @return Collection<int, array{userId: int, name: string, points: int, rank: int}>
     */
    public function daily(string $watDate, int $limit = 50): Collection
    {
        [$start, $end] = $this->watDayWindow($watDate);

        $rows = $this->aggregateScores(
            predictionFilter: fn ($query) => $query->whereBetween('fixtures.kickoff_at', [$start, $end]),
            referralFilter: fn ($query) => $query->where('referrals.wat_date', $watDate),
            limit: $limit,
        );

        return $this->rank($rows);
    }

    /**
     * @param  (callable(Builder): void)|null  $predictionFilter
     * @param  (callable(Builder): void)|null  $referralFilter
     * @return Collection<int, \stdClass>
     */
    private function aggregateScores(?callable $predictionFilter, ?callable $referralFilter, int $limit): Collection
    {
        $predictionScores = DB::table('predictions')
            ->join('fixture_markets', 'fixture_markets.id', '=', 'predictions.fixture_market_id')
            ->join('fixtures', 'fixtures.id', '=', 'fixture_markets.fixture_id')
            ->whereNotNull('predictions.points_awarded')
            ->select(
                'predictions.user_id',
                DB::raw('SUM(predictions.points_awarded) as points'),
                DB::raw('MIN(predictions.created_at) as first_at'),
            )
            ->groupBy('predictions.user_id');

        if ($predictionFilter !== null) {
            $predictionFilter($predictionScores);
        }

        $referralScores = DB::table('referrals')
            ->select(
                'referrals.referrer_id as user_id',
                DB::raw('SUM(referrals.points) as points'),
                DB::raw('MIN(referrals.credited_at) as first_at'),
            )
            ->groupBy('referrals.referrer_id');

        if ($referralFilter !== null) {
            $referralFilter($referralScores);
        }

        return DB::query()
            ->fromSub($predictionScores->unionAll($referralScores), 'scores')
            ->join('users', 'users.id', '=', 'scores.user_id')
            ->groupBy('users.id', 'users.name')
            ->select(
                'users.id',
                'users.name',
                DB::raw('SUM(scores.points) as points'),
                DB::raw('MIN(scores.first_at) as first_at'),
            )
            ->orderByDesc('points')
            ->orderBy('first_at')
            ->limit($limit)
            ->get();
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
