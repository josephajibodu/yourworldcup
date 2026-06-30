<?php

namespace App\Admin;

use App\Bracket\GroupStandingsService;
use App\Enums\FixtureStatus;
use App\Enums\MarketStatus;
use App\Fixtures\FixtureScorePresenter;
use App\Models\BracketSlot;
use App\Models\Fixture;
use App\Models\Prediction;
use App\Models\Referral;
use App\Models\User;
use App\Predictions\Leaderboard\LeaderboardService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AdminDashboardService
{
    private const int GRAND_PRIZE_PLAYER_TARGET = 1000;

    public function __construct(
        private LeaderboardService $leaderboard,
        private GroupStandingsService $standings,
        private FixtureScorePresenter $scores,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function summary(): array
    {
        $today = Carbon::now(config('predictions.timezone'))->toDateString();
        $openFixtures = $this->openFixtures();

        return [
            'stats' => [
                'users' => [
                    'total' => User::query()->count(),
                    'verified' => User::query()->whereNotNull('email_verified_at')->count(),
                    'withPredictions' => User::query()->whereHas('predictions')->count(),
                    'grandPrizeTarget' => self::GRAND_PRIZE_PLAYER_TARGET,
                ],
                'predictions' => [
                    'total' => Prediction::query()->count(),
                    'unscored' => Prediction::query()->whereNull('scored_at')->count(),
                ],
                'referrals' => [
                    'total' => Referral::query()->count(),
                ],
                'fixtures' => [
                    'total' => Fixture::query()->count(),
                    'live' => $openFixtures->filter(fn (Fixture $fixture): bool => $fixture->isLive())->count(),
                    'today' => Fixture::query()->onWatDate($today)->count(),
                    'final' => Fixture::query()->where('status', FixtureStatus::Final)->count(),
                    'unsettledFinal' => Fixture::query()
                        ->where('status', FixtureStatus::Final)
                        ->whereHas('markets', fn ($query) => $query->where('status', '!=', MarketStatus::Settled))
                        ->count(),
                ],
                'bracketSlots' => [
                    'total' => BracketSlot::query()->whereNotNull('feeds_fixture_id')->count(),
                    'assigned' => BracketSlot::query()
                        ->whereNotNull('feeds_fixture_id')
                        ->whereNotNull('resolved_team_id')
                        ->count(),
                ],
                'tournament' => [
                    'allGroupsComplete' => $this->standings->allGroupsComplete(),
                ],
            ],
            'leaderboardTop' => $this->leaderboard->overall(5)->values()->all(),
            'liveFixtures' => $openFixtures
                ->filter(fn (Fixture $fixture): bool => $fixture->isLive())
                ->map(fn (Fixture $fixture): array => $this->formatFixture($fixture))
                ->values()
                ->all(),
            'upcomingFixtures' => $openFixtures
                ->filter(fn (Fixture $fixture): bool => $fixture->kickoff_at->isFuture())
                ->take(5)
                ->map(fn (Fixture $fixture): array => $this->formatFixture($fixture))
                ->values()
                ->all(),
            'fixturesNeedingSettlement' => Fixture::query()
                ->where('status', FixtureStatus::Final)
                ->whereHas('markets', fn ($query) => $query->where('status', '!=', MarketStatus::Settled))
                ->with(['homeTeam:id,name', 'awayTeam:id,name'])
                ->withCount([
                    'markets',
                    'markets as unsettled_markets_count' => fn ($query) => $query->where('status', '!=', MarketStatus::Settled),
                ])
                ->orderByDesc('kickoff_at')
                ->limit(5)
                ->get()
                ->map(fn (Fixture $fixture): array => [
                    ...$this->formatFixture($fixture),
                    'unsettledMarketsCount' => $fixture->unsettled_markets_count,
                    'marketsCount' => $fixture->markets_count,
                ])
                ->all(),
            'unassignedBracketSlots' => BracketSlot::query()
                ->whereNotNull('feeds_fixture_id')
                ->whereNull('resolved_team_id')
                ->with('feedsFixture:id,external_id,stage')
                ->orderBy('feeds_fixture_id')
                ->limit(8)
                ->get()
                ->map(fn (BracketSlot $slot): array => [
                    'id' => $slot->id,
                    'label' => $slot->label,
                    'displayCode' => $slot->displayCode(),
                    'feedsFixtureExternalId' => $slot->feedsFixture?->external_id,
                    'feedsFixtureStageLabel' => $slot->feedsFixture?->stage->label(),
                ])
                ->all(),
        ];
    }

    /**
     * @return Collection<int, Fixture>
     */
    private function openFixtures()
    {
        return Fixture::query()
            ->with(['homeTeam:id,name', 'awayTeam:id,name'])
            ->whereNotNull('home_team_id')
            ->whereNotNull('away_team_id')
            ->whereNotIn('status', [FixtureStatus::Final, FixtureStatus::Void])
            ->orderBy('kickoff_at')
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    private function formatFixture(Fixture $fixture): array
    {
        return [
            'id' => $fixture->id,
            'externalId' => $fixture->external_id,
            'stageLabel' => $fixture->stage->label(),
            'groupCode' => $fixture->group_code,
            'status' => $fixture->status->value,
            'kickoffAt' => $fixture->kickoff_at->toIso8601String(),
            'homeTeam' => $fixture->homeTeam?->name,
            'awayTeam' => $fixture->awayTeam?->name,
            ...$this->scores->present($fixture, onlyWhenVisible: false),
        ];
    }
}
