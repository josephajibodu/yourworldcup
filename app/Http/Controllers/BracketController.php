<?php

namespace App\Http\Controllers;

use App\Bracket\GroupStandingsService;
use App\Cache\TournamentCache;
use App\Enums\BracketSlotSide;
use App\Enums\FixtureStage;
use App\Enums\FixtureStatus;
use App\Models\BracketSlot;
use App\Models\Fixture;
use App\Models\Stadium;
use App\Models\Team;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class BracketController extends Controller
{
    public function __construct(
        private GroupStandingsService $standings,
        private TournamentCache $cache,
    ) {}

    public function index(): Response
    {
        return Inertia::render('bracket', [
            'groups' => $this->groups(),
            'knockout' => $this->knockout(),
            'focusNodeId' => $this->focusNodeId(),
        ]);
    }

    /**
     * React Flow node id for the live or next group / knockout match.
     */
    private function focusNodeId(): ?string
    {
        $fixtures = Fixture::query()
            ->whereNotNull('home_team_id')
            ->whereNotNull('away_team_id')
            ->whereNotIn('status', [FixtureStatus::Final, FixtureStatus::Void])
            ->with(['homeTeam', 'awayTeam'])
            ->orderBy('kickoff_at')
            ->get();

        $live = $fixtures->first(fn (Fixture $fixture): bool => $fixture->isLive());

        if ($live !== null) {
            return $this->nodeIdForFixture($live);
        }

        $next = $fixtures->first(
            fn (Fixture $fixture): bool => $fixture->kickoff_at->isFuture(),
        );

        if ($next !== null) {
            return $this->nodeIdForFixture($next);
        }

        $recent = Fixture::query()
            ->whereNotNull('home_team_id')
            ->whereNotNull('away_team_id')
            ->orderByDesc('kickoff_at')
            ->with(['homeTeam', 'awayTeam'])
            ->first();

        return $recent !== null ? $this->nodeIdForFixture($recent) : null;
    }

    private function nodeIdForFixture(Fixture $fixture): ?string
    {
        if ($fixture->stage === FixtureStage::Group) {
            $groupCode = $fixture->group_code
                ?? $fixture->homeTeam?->group_code
                ?? $fixture->awayTeam?->group_code;

            return $groupCode !== null ? "g{$groupCode}" : null;
        }

        return $fixture->external_id !== null
            ? 'm'.(int) $fixture->external_id
            : null;
    }

    /**
     * The twelve group tables with computed standings.
     *
     * @return array<int, array<string, mixed>>
     */
    private function groups(): array
    {
        $openFixturesByGroup = Fixture::query()
            ->where('stage', FixtureStage::Group)
            ->whereNotNull('group_code')
            ->whereNotNull('home_team_id')
            ->whereNotNull('away_team_id')
            ->whereNotIn('status', [FixtureStatus::Final, FixtureStatus::Void])
            ->get()
            ->groupBy('group_code');

        $teams = Team::query()
            ->whereNotNull('group_code')
            ->orderBy('group_code')
            ->orderByExternalId()
            ->get();

        return $teams
            ->groupBy('group_code')
            ->map(fn (Collection $groupTeams, string $code): array => [
                'code' => $code,
                'teams' => $this->standings->standingsForGroup($code),
                'fixtures' => ($openFixturesByGroup[$code] ?? collect())
                    ->map(fn (Fixture $fixture): array => [
                        'kickoffAt' => $fixture->kickoff_at->toIso8601String(),
                        'status' => $fixture->status->value,
                        'homeTeamId' => $fixture->home_team_id,
                        'awayTeamId' => $fixture->away_team_id,
                    ])
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * The knockout tree, with each slot resolved to a team (once known) or a
     * placeholder label from bracket_slots / feeder config.
     *
     * @return array<int, array<string, mixed>>
     */
    private function knockout(): array
    {
        return $this->cache->remember(
            'bracket',
            'knockout',
            fn (): array => $this->buildKnockout(),
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildKnockout(): array
    {
        /** @var array<int, array{0: int, 1: int}> $feeders */
        $feeders = config('bracket.feeders', []);

        return Fixture::query()
            ->whereIn('stage', [
                FixtureStage::Round32,
                FixtureStage::Round16,
                FixtureStage::QuarterFinal,
                FixtureStage::SemiFinal,
                FixtureStage::ThirdPlace,
                FixtureStage::Final,
            ])
            ->with(['homeTeam', 'awayTeam', 'stadium', 'bracketSlots'])
            ->orderByExternalId()
            ->get()
            ->map(function (Fixture $fixture) use ($feeders): array {
                $id = (int) $fixture->external_id;
                $isThird = $fixture->stage === FixtureStage::ThirdPlace;

                $homeSlot = $fixture->bracketSlots->first(
                    fn (BracketSlot $slot): bool => $slot->side === BracketSlotSide::Home,
                );
                $awaySlot = $fixture->bracketSlots->first(
                    fn (BracketSlot $slot): bool => $slot->side === BracketSlotSide::Away,
                );
                $pair = $this->feedersForFixture($homeSlot, $awaySlot, $feeders[$id] ?? null);
                $showpiece = $this->showpieceMeta($fixture);

                return [
                    'id' => $id,
                    'code' => 'M'.$id,
                    'stage' => $fixture->stage->value,
                    'stageLabel' => $fixture->stage->label(),
                    'status' => $fixture->status->value,
                    'kickoffAt' => $fixture->kickoff_at->toIso8601String(),
                    'stadium' => $fixture->stadium?->name,
                    'city' => $fixture->stadium?->city,
                    'headline' => $showpiece['headline'],
                    'location' => $showpiece['location'],
                    'broadcast' => $showpiece['broadcast'],
                    'timezone' => $showpiece['timezone'],
                    'home' => $this->slot($fixture->homeTeam, $homeSlot, $pair[0] ?? null, $isThird),
                    'away' => $this->slot($fixture->awayTeam, $awaySlot, $pair[1] ?? null, $isThird),
                    'homeScore' => $fixture->status === FixtureStatus::Final ? $fixture->home_score : null,
                    'awayScore' => $fixture->status === FixtureStatus::Final ? $fixture->away_score : null,
                    'feeders' => $pair,
                ];
            })
            ->all();
    }

    /**
     * @param  array{0: int, 1: int}|null  $configFeeders
     * @return array{0: int, 1: int}|null
     */
    private function feedersForFixture(?BracketSlot $homeSlot, ?BracketSlot $awaySlot, ?array $configFeeders): ?array
    {
        $homeFeeder = $homeSlot?->knockoutFeederMatchId();
        $awayFeeder = $awaySlot?->knockoutFeederMatchId();

        if ($homeFeeder !== null && $awayFeeder !== null) {
            return [$homeFeeder, $awayFeeder];
        }

        return $configFeeders;
    }

    /**
     * @return array{headline: ?string, location: ?string, broadcast: ?string, timezone: ?string}
     */
    private function showpieceMeta(Fixture $fixture): array
    {
        if (! in_array($fixture->stage, [FixtureStage::Final, FixtureStage::ThirdPlace], true)) {
            return [
                'headline' => null,
                'location' => null,
                'broadcast' => null,
                'timezone' => null,
            ];
        }

        $externalId = (string) $fixture->external_id;

        return [
            'headline' => config("bracket.showpiece_headlines.{$externalId}"),
            'location' => $fixture->stadium !== null
                ? $this->formatVenueLabel($fixture->stadium)
                : null,
            'broadcast' => config("bracket.showpiece_broadcasters.{$externalId}"),
            'timezone' => $fixture->stadium?->timezone,
        ];
    }

    private function formatVenueLabel(Stadium $stadium): string
    {
        $city = $stadium->city ?? '';

        if (preg_match('/\(([^)]+)\)/', $city, $matches)) {
            $locality = trim($matches[1]);
        } else {
            $locality = trim(explode('/', $city)[0] ?? $city);
        }

        $state = config("bracket.stadium_states.{$stadium->external_id}");

        return is_string($state) && $state !== ''
            ? "{$locality}, {$state}"
            : $locality;
    }

    /**
     * @return array<string, mixed>
     */
    private function slot(?Team $team, ?BracketSlot $bracketSlot, ?int $feeder, bool $isThird): array
    {
        $feederLabel = $feeder !== null ? ($isThird ? 'RU' : 'W').$feeder : null;

        return [
            'team' => $team !== null
                ? ['name' => $team->name, 'code' => $team->code, 'flag' => $team->flag]
                : null,
            'label' => $bracketSlot?->displayCode() ?? $feederLabel,
        ];
    }
}
