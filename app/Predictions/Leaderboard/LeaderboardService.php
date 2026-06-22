<?php

namespace App\Predictions\Leaderboard;

use App\Cache\TournamentCache;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LeaderboardService
{
    public function __construct(private TournamentCache $cache) {}

    /**
     * Cumulative tournament standings: users who have submitted at least one
     * prediction, ranked by prediction + referral points.
     *
     * @return Collection<int, array{userId: int, name: string, points: int, rank: int}>
     */
    public function overall(int $limit = 50): Collection
    {
        return collect($this->cache->remember(
            'leaderboard',
            "overall:{$limit}",
            fn (): array => $this->computeOverall($limit)->all(),
        ));
    }

    /**
     * Standings for a single WAT week (Mon–Sun): users who predicted that
     * week's fixtures or earned a referral credit that week, ranked by points.
     *
     * @return Collection<int, array{userId: int, name: string, points: int, rank: int}>
     */
    public function weekly(string $weekStart, int $limit = 50): Collection
    {
        return collect($this->cache->remember(
            'leaderboard',
            "weekly:{$weekStart}:{$limit}",
            fn (): array => $this->computeWeekly($weekStart, $limit)->all(),
        ));
    }

    /**
     * WAT week-start dates (Mondays) that have weekly-board activity:
     * predictions on that week's fixtures or referral credits earned that week.
     *
     * @return array<int, string>
     */
    public function weeklyDates(): array
    {
        return $this->cache->remember(
            'leaderboard',
            'weeks',
            fn (): array => $this->computeWeeklyDates(),
        );
    }

    /**
     * @return Collection<int, array{userId: int, name: string, points: int, rank: int}>
     */
    private function computeOverall(int $limit): Collection
    {
        $rows = DB::query()
            ->fromSub($this->overallParticipantsSubquery(), 'participants')
            ->join('users', 'users.id', '=', 'participants.user_id')
            ->leftJoinSub($this->combinedScoreSubquery(), 'scores', 'users.id', '=', 'scores.user_id')
            ->select(
                'users.id',
                'users.name',
                DB::raw('COALESCE(scores.points, 0) as points'),
                DB::raw('COALESCE(scores.first_at, participants.first_at) as first_at'),
            )
            ->orderByDesc('points')
            ->orderBy('first_at')
            ->limit($limit)
            ->get();

        return $this->rank($rows);
    }

    /**
     * @return Collection<int, array{userId: int, name: string, points: int, rank: int}>
     */
    private function computeWeekly(string $weekStart, int $limit): Collection
    {
        [$start, $end] = $this->watWeekWindow($weekStart);
        $weekEnd = Carbon::parse($weekStart, config('predictions.timezone'))->addDays(6)->toDateString();

        $rows = DB::query()
            ->fromSub($this->weeklyParticipantsSubquery($start, $end, $weekStart, $weekEnd), 'participants')
            ->join('users', 'users.id', '=', 'participants.user_id')
            ->leftJoinSub(
                $this->combinedScoreSubquery(
                    predictionFilter: fn (Builder $query) => $query->whereBetween('fixtures.kickoff_at', [$start, $end]),
                    referralFilter: fn (Builder $query) => $query->whereBetween('referrals.wat_date', [$weekStart, $weekEnd]),
                ),
                'scores',
                'users.id',
                '=',
                'scores.user_id',
            )
            ->select(
                'users.id',
                'users.name',
                DB::raw('COALESCE(scores.points, 0) as points'),
                DB::raw('COALESCE(scores.first_at, participants.first_at) as first_at'),
            )
            ->orderByDesc('points')
            ->orderBy('first_at')
            ->limit($limit)
            ->get();

        return $this->rank($rows);
    }

    /**
     * @return array<int, string>
     */
    private function computeWeeklyDates(): array
    {
        $timezone = config('predictions.timezone');

        $firstWeek = Carbon::parse('2026-06-11', $timezone)->startOfWeek(Carbon::MONDAY);
        $currentWeek = Carbon::now($timezone)->startOfWeek(Carbon::MONDAY);

        $weeks = [];
        $week = $firstWeek->copy();

        while ($week->lte($currentWeek)) {
            $weeks[] = $week->toDateString();
            $week->addWeek();
        }

        return $weeks;
    }

    /**
     * @param  (callable(Builder): void)|null  $predictionFilter
     * @param  (callable(Builder): void)|null  $referralFilter
     */
    private function combinedScoreSubquery(
        ?callable $predictionFilter = null,
        ?callable $referralFilter = null,
    ): Builder {
        $predictionScores = DB::table('predictions')
            ->join('fixture_markets', 'fixture_markets.id', '=', 'predictions.fixture_market_id')
            ->join('fixtures', 'fixtures.id', '=', 'fixture_markets.fixture_id')
            ->whereNotNull('predictions.points_awarded')
            ->select(
                'predictions.user_id',
                DB::raw('SUM(predictions.points_awarded) as points'),
                DB::raw('MIN(predictions.submitted_at) as first_at'),
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
            ->groupBy('scores.user_id')
            ->select(
                'scores.user_id',
                DB::raw('SUM(scores.points) as points'),
                DB::raw('MIN(scores.first_at) as first_at'),
            );
    }

    private function overallParticipantsSubquery(): Builder
    {
        return DB::table('predictions')
            ->select(
                'predictions.user_id',
                DB::raw('MIN(predictions.submitted_at) as first_at'),
            )
            ->groupBy('predictions.user_id');
    }

    private function weeklyParticipantsSubquery(Carbon $start, Carbon $end, string $weekStart, string $weekEnd): Builder
    {
        $fromPredictions = DB::table('predictions')
            ->join('fixture_markets', 'fixture_markets.id', '=', 'predictions.fixture_market_id')
            ->join('fixtures', 'fixtures.id', '=', 'fixture_markets.fixture_id')
            ->whereBetween('fixtures.kickoff_at', [$start, $end])
            ->select(
                'predictions.user_id',
                DB::raw('MIN(predictions.submitted_at) as first_at'),
            )
            ->groupBy('predictions.user_id');

        $fromReferrals = DB::table('referrals')
            ->whereBetween('referrals.wat_date', [$weekStart, $weekEnd])
            ->select(
                'referrals.referrer_id as user_id',
                DB::raw('MIN(referrals.credited_at) as first_at'),
            )
            ->groupBy('referrals.referrer_id');

        return DB::query()
            ->fromSub($fromPredictions->unionAll($fromReferrals), 'participants')
            ->groupBy('participants.user_id')
            ->select(
                'participants.user_id',
                DB::raw('MIN(participants.first_at) as first_at'),
            );
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
     * Returns the UTC [start, end) window for a WAT week beginning on $weekStart (a Monday).
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function watWeekWindow(string $weekStart): array
    {
        $start = Carbon::parse($weekStart, config('predictions.timezone'))->startOfDay();

        return [$start->copy()->utc(), $start->copy()->addWeek()->utc()];
    }
}
