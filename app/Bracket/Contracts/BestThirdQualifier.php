<?php

namespace App\Bracket\Contracts;

use App\Models\Team;
use Illuminate\Support\Collection;

interface BestThirdQualifier
{
    /**
     * Rank all third-place teams for display and tiebreak order.
     *
     * @param  Collection<int, array{team: Team, row: array<string, mixed>, groupComplete: bool}>  $thirdPlaceTeams
     * @return Collection<int, array{rank: int, qualifies: bool, team: Team, row: array<string, mixed>, groupComplete: bool}>
     */
    public function rankAll(Collection $thirdPlaceTeams): Collection;

    /**
     * Rank third-place teams and return the qualifiers for the round of 32.
     *
     * @param  Collection<int, array{team: Team, row: array<string, mixed>, groupComplete: bool}>  $thirdPlaceTeams
     * @return Collection<int, Team>
     */
    public function qualify(Collection $thirdPlaceTeams): Collection;
}
