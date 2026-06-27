<?php

namespace App\Bracket;

use App\Bracket\Contracts\BestThirdQualifier;
use App\Enums\BracketSlotSide;
use App\Enums\BracketSlotType;
use App\Enums\FixtureStage;
use App\Enums\FixtureStatus;
use App\Models\BracketSlot;
use App\Models\Fixture;
use App\Models\Team;
use Illuminate\Support\Facades\DB;

class BracketResolverService
{
    public function __construct(
        private GroupStandingsService $standings,
        private BestThirdQualifier $bestThirdQualifier,
    ) {}

    /**
     * Resolve every slot that can be filled from the given fixture result.
     */
    public function resolveFromFixture(Fixture $fixture): int
    {
        $resolved = 0;

        if ($fixture->stage === FixtureStage::Group && $fixture->status === FixtureStatus::Final) {
            $groupCode = $fixture->group_code
                ?? $fixture->homeTeam?->group_code
                ?? $fixture->awayTeam?->group_code;

            if ($groupCode !== null && $this->standings->isGroupComplete($groupCode)) {
                $resolved += $this->resolveGroup($groupCode);
            }

            if ($this->standings->allGroupsComplete()) {
                $resolved += $this->resolveBestThirds();
            }

            return $resolved;
        }

        if ($fixture->stage->isKnockout() && $fixture->status === FixtureStatus::Final) {
            return $this->resolveKnockoutFeeders($fixture);
        }

        return $resolved;
    }

    public function resolveAllAvailable(): int
    {
        $resolved = 0;

        $groups = Team::query()
            ->whereNotNull('group_code')
            ->distinct()
            ->pluck('group_code');

        foreach ($groups as $groupCode) {
            if ($this->standings->isGroupComplete((string) $groupCode)) {
                $resolved += $this->resolveGroup((string) $groupCode);
            }
        }

        if ($this->standings->allGroupsComplete()) {
            $resolved += $this->resolveBestThirds();
        }

        $fixtures = Fixture::query()
            ->where('status', FixtureStatus::Final)
            ->whereNot('stage', FixtureStage::Group)
            ->get();

        foreach ($fixtures as $fixture) {
            $resolved += $this->resolveKnockoutFeeders($fixture);
        }

        return $resolved;
    }

    public function resolveGroup(string $groupCode): int
    {
        if (! $this->standings->isGroupComplete($groupCode)) {
            return 0;
        }

        $resolved = 0;

        $winner = $this->standings->teamAtPosition($groupCode, 1, fresh: true);
        $runnerUp = $this->standings->teamAtPosition($groupCode, 2, fresh: true);

        $slots = BracketSlot::query()
            ->whereIn('slot_type', [
                BracketSlotType::GroupWinner,
                BracketSlotType::GroupRunnerUp,
            ])
            ->where('slot_spec->group', $groupCode)
            ->with('feedsFixture')
            ->get();

        foreach ($slots as $slot) {
            $row = match ($slot->slot_type) {
                BracketSlotType::GroupWinner => $winner,
                BracketSlotType::GroupRunnerUp => $runnerUp,
                default => null,
            };

            if ($row === null) {
                continue;
            }

            $team = Team::query()->find($row['id']);

            if ($team !== null && $this->resolveSlot($slot, $team)) {
                $resolved++;
            }
        }

        return $resolved;
    }

    public function resolveBestThirds(): int
    {
        if (! $this->standings->allGroupsComplete()) {
            return 0;
        }

        $thirdPlaceTeams = $this->standings->thirdPlaceTeams(fresh: true);

        if ($thirdPlaceTeams->count() < 12) {
            return 0;
        }

        $qualified = $this->bestThirdQualifier->qualify($thirdPlaceTeams);
        $assignedTeamIds = [];

        $slots = BracketSlot::query()
            ->where('slot_type', BracketSlotType::BestThird)
            ->whereNull('resolved_team_id')
            ->with('feedsFixture')
            ->get();

        $resolved = 0;

        foreach ($slots as $slot) {
            /** @var array<int, string> $allowedGroups */
            $allowedGroups = $slot->slot_spec['groups'] ?? [];

            $team = $qualified->first(function (Team $candidate) use ($allowedGroups, $assignedTeamIds): bool {
                if (in_array($candidate->id, $assignedTeamIds, true)) {
                    return false;
                }

                return in_array($candidate->group_code, $allowedGroups, true);
            });

            if ($team === null) {
                continue;
            }

            if ($this->resolveSlot($slot, $team)) {
                $assignedTeamIds[] = $team->id;
                $resolved++;
            }
        }

        return $resolved;
    }

    public function resolveKnockoutFeeders(Fixture $fixture): int
    {
        if ($fixture->external_id === null || $fixture->status !== FixtureStatus::Final) {
            return 0;
        }

        $matchId = (string) $fixture->external_id;
        $resolved = 0;

        $winnerSlots = BracketSlot::query()
            ->where('slot_type', BracketSlotType::KnockoutWinner)
            ->where('slot_spec->match', $matchId)
            ->with('feedsFixture')
            ->get();

        foreach ($winnerSlots as $slot) {
            $winner = $fixture->winner_team_id !== null
                ? Team::query()->find($fixture->winner_team_id)
                : null;

            if ($winner !== null && $this->resolveSlot($slot, $winner)) {
                $resolved++;
            }
        }

        $loserSlots = BracketSlot::query()
            ->where('slot_type', BracketSlotType::KnockoutLoser)
            ->where('slot_spec->match', $matchId)
            ->with('feedsFixture')
            ->get();

        foreach ($loserSlots as $slot) {
            $loser = $this->loserOf($fixture);

            if ($loser !== null && $this->resolveSlot($slot, $loser)) {
                $resolved++;
            }
        }

        return $resolved;
    }

    private function loserOf(Fixture $fixture): ?Team
    {
        if ($fixture->winner_team_id === null) {
            return null;
        }

        if ($fixture->winner_team_id === $fixture->home_team_id) {
            return $fixture->awayTeam;
        }

        if ($fixture->winner_team_id === $fixture->away_team_id) {
            return $fixture->homeTeam;
        }

        return null;
    }

    private function resolveSlot(BracketSlot $slot, Team $team): bool
    {
        if ($slot->resolved_team_id === $team->id) {
            return false;
        }

        return DB::transaction(function () use ($slot, $team): bool {
            $slot->update(['resolved_team_id' => $team->id]);

            $feedsFixture = $slot->feedsFixture;

            if ($feedsFixture === null) {
                return true;
            }

            $column = $slot->side === BracketSlotSide::Home ? 'home_team_id' : 'away_team_id';

            if ($feedsFixture->{$column} === $team->id) {
                return true;
            }

            $feedsFixture->update([$column => $team->id]);

            return true;
        });
    }
}
