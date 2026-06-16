<?php

namespace App\Http\Controllers;

use App\Enums\FixtureStage;
use App\Enums\FixtureStatus;
use App\Models\Fixture;
use App\Models\Team;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class BracketController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('bracket', [
            'groups' => $this->groups(),
            'knockout' => $this->knockout(),
        ]);
    }

    /**
     * The twelve group tables with computed standings.
     *
     * @return array<int, array<string, mixed>>
     */
    private function groups(): array
    {
        $teams = Team::query()
            ->whereNotNull('group_code')
            ->orderBy('group_code')
            ->orderByRaw('CAST(external_id AS INTEGER)')
            ->get();

        $finished = Fixture::query()
            ->where('stage', FixtureStage::Group)
            ->where('status', FixtureStatus::Final)
            ->get();

        return $teams
            ->groupBy('group_code')
            ->map(fn (Collection $groupTeams, string $code): array => [
                'code' => $code,
                'teams' => $this->standings($groupTeams, $finished),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Team>  $teams
     * @param  Collection<int, Fixture>  $finished
     * @return array<int, array<string, mixed>>
     */
    private function standings(Collection $teams, Collection $finished): array
    {
        $rows = $teams->mapWithKeys(fn (Team $team): array => [$team->id => [
            'id' => $team->id,
            'name' => $team->name,
            'code' => $team->code,
            'flag' => $team->flag,
            'played' => 0,
            'won' => 0,
            'drawn' => 0,
            'lost' => 0,
            'gf' => 0,
            'ga' => 0,
            'gd' => 0,
            'points' => 0,
        ]])->all();

        foreach ($finished as $fixture) {
            if (! isset($rows[$fixture->home_team_id], $rows[$fixture->away_team_id])) {
                continue;
            }

            $home = $fixture->home_score ?? 0;
            $away = $fixture->away_score ?? 0;
            $this->applyResult($rows[$fixture->home_team_id], $home, $away);
            $this->applyResult($rows[$fixture->away_team_id], $away, $home);
        }

        $rows = array_values($rows);

        usort($rows, function (array $a, array $b): int {
            return [$b['points'], $b['gd'], $b['gf']] <=> [$a['points'], $a['gd'], $a['gf']]
                ?: strcmp($a['name'], $b['name']);
        });

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function applyResult(array &$row, int $for, int $against): void
    {
        $row['played']++;
        $row['gf'] += $for;
        $row['ga'] += $against;
        $row['gd'] = $row['gf'] - $row['ga'];

        if ($for > $against) {
            $row['won']++;
            $row['points'] += 3;
        } elseif ($for === $against) {
            $row['drawn']++;
            $row['points'] += 1;
        } else {
            $row['lost']++;
        }
    }

    /**
     * The knockout tree, with each slot resolved to a team (once known) or a
     * placeholder label ("W83" winner-of, "RU101" runner-up-of, or null = TBD).
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
            ->with(['homeTeam', 'awayTeam', 'stadium'])
            ->orderByRaw('CAST(external_id AS INTEGER)')
            ->get()
            ->map(function (Fixture $fixture) use ($feeders): array {
                $id = (int) $fixture->external_id;
                $pair = $feeders[$id] ?? null;
                $isThird = $fixture->stage === FixtureStage::ThirdPlace;

                return [
                    'id' => $id,
                    'code' => 'M'.$id,
                    'stage' => $fixture->stage->value,
                    'stageLabel' => $fixture->stage->label(),
                    'kickoffAt' => $fixture->kickoff_at->toIso8601String(),
                    'stadium' => $fixture->stadium?->name,
                    'city' => $fixture->stadium?->city,
                    'home' => $this->slot($fixture->homeTeam, $pair[0] ?? null, $isThird),
                    'away' => $this->slot($fixture->awayTeam, $pair[1] ?? null, $isThird),
                    'feeders' => $pair,
                ];
            })
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function slot(?Team $team, ?int $feeder, bool $isThird): array
    {
        $label = $feeder !== null ? ($isThird ? 'RU' : 'W').$feeder : null;

        return [
            'team' => $team !== null
                ? ['name' => $team->name, 'code' => $team->code, 'flag' => $team->flag]
                : null,
            'label' => $label,
        ];
    }
}
