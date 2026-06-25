<?php

namespace App\Bracket;

use App\Enums\BracketSlotType;
use App\Enums\FixtureStatus;
use App\Models\BracketSlot;
use App\Models\Team;
use Illuminate\Support\Collection;

class BracketSlotEligibility
{
    public function __construct(private GroupStandingsService $standings) {}

    /**
     * @param  Collection<int, int>  $assignedTeamIds
     * @return Collection<int, Team>
     */
    public function eligibleTeams(BracketSlot $slot, Collection $assignedTeamIds): Collection
    {
        return $this->teamsMatchingSlot($slot)
            ->filter(function (Team $team) use ($slot, $assignedTeamIds): bool {
                if ($team->id === $slot->resolved_team_id) {
                    return true;
                }

                return ! $assignedTeamIds->contains($team->id);
            })
            ->values();
    }

    /**
     * @return Collection<int, int>
     */
    public function assignedTeamIds(?int $exceptSlotId = null): Collection
    {
        return BracketSlot::query()
            ->when($exceptSlotId !== null, fn ($query) => $query->whereKeyNot($exceptSlotId))
            ->whereNotNull('resolved_team_id')
            ->pluck('resolved_team_id');
    }

    public function isEligible(BracketSlot $slot, Team $team): bool
    {
        return $this->eligibleTeams($slot, $this->assignedTeamIds($slot->id))
            ->contains(fn (Team $candidate): bool => $candidate->is($team));
    }

    /**
     * @return Collection<int, Team>
     */
    private function teamsMatchingSlot(BracketSlot $slot): Collection
    {
        return match ($slot->slot_type) {
            BracketSlotType::GroupWinner,
            BracketSlotType::GroupRunnerUp => $this->teamsInGroup((string) ($slot->slot_spec['group'] ?? '')),
            BracketSlotType::BestThird => $this->teamsInGroups($slot->slot_spec['groups'] ?? []),
            BracketSlotType::KnockoutWinner,
            BracketSlotType::KnockoutLoser => $this->teamsFromSourceFixture($slot),
        };
    }

    /**
     * @return Collection<int, Team>
     */
    private function teamsInGroup(string $groupCode): Collection
    {
        if ($groupCode === '') {
            return collect();
        }

        return Team::query()
            ->where('group_code', $groupCode)
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  array<int, string>  $groupCodes
     * @return Collection<int, Team>
     */
    private function teamsInGroups(array $groupCodes): Collection
    {
        if ($groupCodes === []) {
            return collect();
        }

        return Team::query()
            ->whereIn('group_code', $groupCodes)
            ->orderBy('group_code')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, Team>
     */
    private function teamsFromSourceFixture(BracketSlot $slot): Collection
    {
        $source = $slot->sourceFixture;

        if ($source === null || $source->home_team_id === null || $source->away_team_id === null) {
            return collect();
        }

        if ($source->status !== FixtureStatus::Final) {
            return Team::query()
                ->whereIn('id', [$source->home_team_id, $source->away_team_id])
                ->orderBy('name')
                ->get();
        }

        if ($source->winner_team_id === null) {
            return Team::query()
                ->whereIn('id', [$source->home_team_id, $source->away_team_id])
                ->orderBy('name')
                ->get();
        }

        $loserId = $source->winner_team_id === $source->home_team_id
            ? $source->away_team_id
            : $source->home_team_id;

        $teamId = match ($slot->slot_type) {
            BracketSlotType::KnockoutWinner => $source->winner_team_id,
            BracketSlotType::KnockoutLoser => $loserId,
            default => null,
        };

        if ($teamId === null) {
            return collect();
        }

        $team = Team::query()->find($teamId);

        return $team === null ? collect() : collect([$team]);
    }

    public function standingPosition(Team $team): ?int
    {
        if ($team->group_code === null) {
            return null;
        }

        $standings = $this->standings->standingsForGroup($team->group_code);

        foreach ($standings as $index => $row) {
            if ($row['id'] === $team->id) {
                return $index + 1;
            }
        }

        return null;
    }
}
