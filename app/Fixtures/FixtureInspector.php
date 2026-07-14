<?php

namespace App\Fixtures;

use App\Models\Fixture;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class FixtureInspector
{
    public function __construct(private FixtureResultRecorder $recorder) {}

    public function findByMatchNumber(string $match): Fixture
    {
        return $this->recorder->findByExternalId(FixtureMatchId::normalize($match));
    }

    /**
     * @return array<string, mixed>
     */
    public function summarize(Fixture $fixture): array
    {
        $fixture->loadMissing(['homeTeam', 'awayTeam', 'stadium', 'markets.market']);

        return [
            'id' => $fixture->id,
            'externalId' => $fixture->external_id,
            'stage' => $fixture->stage->label(),
            'status' => $fixture->status->value,
            'kickoffAt' => $fixture->kickoff_at->toIso8601String(),
            'watDate' => $fixture->watDate(),
            'homeTeam' => $fixture->homeTeam?->name,
            'awayTeam' => $fixture->awayTeam?->name,
            'homeCode' => $fixture->homeTeam?->code,
            'awayCode' => $fixture->awayTeam?->code,
            'stadium' => $fixture->stadium?->name,
            'city' => $fixture->stadium?->city,
            'homeScore' => $fixture->home_score,
            'awayScore' => $fixture->away_score,
            'marketsCount' => $fixture->markets->count(),
            'marketNames' => $fixture->markets
                ->sortBy(fn ($market) => $market->market->sort_order)
                ->map(fn ($market): string => $market->market->name)
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  list<string>  $expectedTeams
     */
    public function assertTeams(Fixture $fixture, array $expectedTeams): void
    {
        $fixture->loadMissing(['homeTeam', 'awayTeam']);

        $actual = Collection::make([$fixture->homeTeam?->name, $fixture->awayTeam?->name])
            ->filter()
            ->sort()
            ->values()
            ->all();

        $expected = Collection::make($expectedTeams)->sort()->values()->all();

        if ($actual !== $expected) {
            throw ValidationException::withMessages([
                'fixture' => sprintf(
                    'Expected teams [%s] but found [%s].',
                    implode(', ', $expected),
                    implode(', ', $actual) ?: 'TBD',
                ),
            ]);
        }
    }
}
