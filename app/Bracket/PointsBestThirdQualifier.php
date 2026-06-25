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
     * @param  Collection<int, array{team: Team, row: array<string, mixed>, groupComplete: bool}>  $thirdPlaceTeams
     * @return Collection<int, array{rank: int, qualifies: bool, team: Team, row: array<string, mixed>, groupComplete: bool}>
     */
    public function rankAll(Collection $thirdPlaceTeams): Collection
    {
        return $thirdPlaceTeams
            ->sort(function (array $a, array $b): int {
                return [$b['row']['points'], $b['row']['gd'], $b['row']['gf']] <=> [$a['row']['points'], $a['row']['gd'], $a['row']['gf']]
                    ?: strcmp($a['team']->name, $b['team']->name);
            })
            ->values()
            ->map(function (array $entry, int $index): array {
                $rank = $index + 1;

                return [
                    ...$entry,
                    'rank' => $rank,
                    'qualifies' => $rank <= self::QUALIFIER_COUNT,
                ];
            });
    }

    /**
     * @param  Collection<int, array{team: Team, row: array<string, mixed>, groupComplete: bool}>  $thirdPlaceTeams
     * @return Collection<int, Team>
     */
    public function qualify(Collection $thirdPlaceTeams): Collection
    {
        return $this->rankAll($thirdPlaceTeams)
            ->where('qualifies', true)
            ->map(fn (array $entry): Team => $entry['team'])
            ->values();
    }
}
