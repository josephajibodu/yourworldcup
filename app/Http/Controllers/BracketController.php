<?php

namespace App\Http\Controllers;

use App\Bracket\GroupStandingsService;
use App\Enums\BracketSlotSide;
use App\Enums\FixtureStage;
use App\Enums\FixtureStatus;
use App\Models\BracketSlot;
use App\Models\Fixture;
use App\Models\Team;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class BracketController extends Controller
{
    public function __construct(private GroupStandingsService $standings) {}

    public function index(): Response
    {
        return Inertia::render('bracket', [
            'groups' => $this->groups(),
            'knockout' => $this->knockout(),
            'focusNodeId' => $this->focusNodeId(),
        ]);
    }

    /**
     * React Flow node id for the next open match day — a group table during
     * the group stage, or a knockout match node once the tournament advances.
     */
    private function focusNodeId(): ?string
    {
        $fixture = Fixture::query()
            ->whereNotNull('home_team_id')
            ->whereNotNull('away_team_id')
            ->where('lock_at', '>', now())
            ->orderBy('kickoff_at')
            ->with(['homeTeam', 'awayTeam'])
            ->first();

        if ($fixture === null) {
            $fixture = Fixture::query()
                ->whereNotNull('home_team_id')
                ->whereNotNull('away_team_id')
                ->orderByDesc('kickoff_at')
                ->with(['homeTeam', 'awayTeam'])
                ->first();
        }

        if ($fixture === null) {
            return null;
        }

        if ($fixture->stage === FixtureStage::Group) {
            $groupCode = $fixture->homeTeam?->group_code
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
                $pair = $feeders[$id] ?? null;
                $isThird = $fixture->stage === FixtureStage::ThirdPlace;

                $homeSlot = $fixture->bracketSlots->first(
                    fn (BracketSlot $slot): bool => $slot->side === BracketSlotSide::Home,
                );
                $awaySlot = $fixture->bracketSlots->first(
                    fn (BracketSlot $slot): bool => $slot->side === BracketSlotSide::Away,
                );

                return [
                    'id' => $id,
                    'code' => 'M'.$id,
                    'stage' => $fixture->stage->value,
                    'stageLabel' => $fixture->stage->label(),
                    'status' => $fixture->status->value,
                    'kickoffAt' => $fixture->kickoff_at->toIso8601String(),
                    'stadium' => $fixture->stadium?->name,
                    'city' => $fixture->stadium?->city,
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
