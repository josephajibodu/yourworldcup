<?php

namespace App\Bracket\Contracts;

use App\Models\Team;
use Illuminate\Support\Collection;

interface BestThirdQualifier
{
    /**
     * Rank third-place teams and return the qualifiers for the round of 32.
     *
     * @param  Collection<int, array{team: Team, row: array<string, mixed>}>  $thirdPlaceTeams
     * @return Collection<int, Team>
     */
    public function qualify(Collection $thirdPlaceTeams): Collection;
}
