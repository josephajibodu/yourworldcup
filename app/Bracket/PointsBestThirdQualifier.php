<?php

namespace App\Bracket;

use App\Bracket\Contracts\BestThirdQualifier;
use App\Models\Team;
use Illuminate\Support\Collection;

/**
 * Ranks third-place teams by points, goal difference, then goals scored.
 *
 * Swap this binding for a FIFA-compliant implementation when the tournament rules
 * need full tiebreak logic.
 */
class PointsBestThirdQualifier implements BestThirdQualifier
{
    private const int QUALIFIER_COUNT = 8;

    /**
     * @param  Collection<int, array{team: Team, row: array<string, mixed>}>  $thirdPlaceTeams
     * @return Collection<int, Team>
     */
    public function qualify(Collection $thirdPlaceTeams): Collection
    {
        return $thirdPlaceTeams
            ->sortByDesc(fn (array $entry): array => [
                $entry['row']['points'],
                $entry['row']['gd'],
                $entry['row']['gf'],
            ])
            ->take(self::QUALIFIER_COUNT)
            ->map(fn (array $entry): Team => $entry['team'])
            ->values();
    }
}
