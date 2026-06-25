<?php

namespace App\Bracket;

use App\Cache\TournamentCache;
use App\Enums\FixtureStage;
use App\Enums\FixtureStatus;
use App\Models\Fixture;
use App\Models\Team;
use Illuminate\Support\Collection;

class GroupStandingsService
{
    public function __construct(private TournamentCache $cache) {}

    /**
     * @return array<int, array{id: int, name: string, code: string, flag: string|null, played: int, won: int, drawn: int, lost: int, gf: int, ga: int, gd: int, points: int}>
     */
    public function standingsForGroup(string $groupCode): array
    {
        return $this->cache->remember(
            'bracket',
            "standings:{$groupCode}",
            fn (): array => $this->computeStandingsForGroup($groupCode),
        );
    }

    /**
     * @return array<int, array{id: int, name: string, code: string, flag: string|null, played: int, won: int, drawn: int, lost: int, gf: int, ga: int, gd: int, points: int}>
     */
    public function standingsForAllGroups(): array
    {
        return $this->cache->remember(
            'bracket',
            'standings:all',
            fn (): array => $this->computeStandingsForAllGroups(),
        );
    }

    public function isGroupComplete(string $groupCode): bool
    {
        $expected = Fixture::query()
            ->where('stage', FixtureStage::Group)
            ->where('group_code', $groupCode)
            ->count();

        if ($expected === 0) {
            return false;
        }

        $finished = Fixture::query()
            ->where('stage', FixtureStage::Group)
            ->where('group_code', $groupCode)
            ->where('status', FixtureStatus::Final)
            ->count();

        return $finished === $expected;
    }

    public function allGroupsComplete(): bool
    {
        $groups = Team::query()
            ->whereNotNull('group_code')
            ->distinct()
            ->pluck('group_code');

        foreach ($groups as $groupCode) {
            if (! $this->isGroupComplete((string) $groupCode)) {
                return false;
            }
        }

        return $groups->isNotEmpty();
    }

    /**
     * @return array{id: int, name: string, code: string, flag: string|null, played: int, won: int, drawn: int, lost: int, gf: int, ga: int, gd: int, points: int}|null
     */
    public function teamAtPosition(string $groupCode, int $position): ?array
    {
        $standings = $this->standingsForGroup($groupCode);

        return $standings[$position - 1] ?? null;
    }

    /**
     * @return Collection<int, array{team: Team, row: array<string, mixed>}>
     */
    public function thirdPlaceTeams(): Collection
    {
        return $this->provisionalThirdPlaceTeams()
            ->where('groupComplete', true)
            ->map(fn (array $entry): array => [
                'team' => $entry['team'],
                'row' => $entry['row'],
            ])
            ->values();
    }

    /**
     * Current third-place team in each group from standings so far.
     *
     * @return Collection<int, array{team: Team, row: array<string, mixed>, groupComplete: bool}>
     */
    public function provisionalThirdPlaceTeams(): Collection
    {
        $groups = Team::query()
            ->whereNotNull('group_code')
            ->distinct()
            ->orderBy('group_code')
            ->pluck('group_code');

        return $groups
            ->map(function (string $groupCode): ?array {
                $row = $this->teamAtPosition($groupCode, 3);

                if ($row === null) {
                    return null;
                }

                $team = Team::query()->find($row['id']);

                if ($team === null) {
                    return null;
                }

                return [
                    'team' => $team,
                    'row' => $row,
                    'groupComplete' => $this->isGroupComplete($groupCode),
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * @return array<int, array{id: int, name: string, code: string, flag: string|null, played: int, won: int, drawn: int, lost: int, gf: int, ga: int, gd: int, points: int}>
     */
    private function computeStandingsForGroup(string $groupCode): array
    {
        $teams = Team::query()
            ->where('group_code', $groupCode)
            ->orderByExternalId()
            ->get();

        $finished = Fixture::query()
            ->where('stage', FixtureStage::Group)
            ->where('group_code', $groupCode)
            ->where('status', FixtureStatus::Final)
            ->get();

        return $this->buildStandings($teams, $finished);
    }

    /**
     * @return array<int, array{id: int, name: string, code: string, flag: string|null, played: int, won: int, drawn: int, lost: int, gf: int, ga: int, gd: int, points: int}>
     */
    private function computeStandingsForAllGroups(): array
    {
        $teams = Team::query()
            ->whereNotNull('group_code')
            ->orderBy('group_code')
            ->orderByExternalId()
            ->get();

        $finished = Fixture::query()
            ->where('stage', FixtureStage::Group)
            ->where('status', FixtureStatus::Final)
            ->get();

        return $this->buildStandings($teams, $finished);
    }

    /**
     * @param  Collection<int, Team>  $teams
     * @param  Collection<int, Fixture>  $finished
     * @return array<int, array{id: int, name: string, code: string, flag: string|null, played: int, won: int, drawn: int, lost: int, gf: int, ga: int, gd: int, points: int}>
     */
    private function buildStandings(Collection $teams, Collection $finished): array
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
}
