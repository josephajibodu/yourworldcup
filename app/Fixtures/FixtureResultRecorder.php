<?php

namespace App\Fixtures;

use App\Cache\TournamentCache;
use App\Enums\FixtureStatus;
use App\Models\Fixture;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class FixtureResultRecorder
{
    public function __construct(private TournamentCache $cache) {}

    /**
     * @return array<int, array{id: string, home: int, away: int}>
     */
    public function parseBulkInput(string $json): array
    {
        $payload = json_decode($json, true);

        if (! is_array($payload) || ! array_is_list($payload)) {
            throw ValidationException::withMessages([
                'json' => 'Bulk results must be a JSON array.',
            ]);
        }

        $entries = [];

        foreach ($payload as $index => $row) {
            if (! is_array($row)) {
                throw ValidationException::withMessages([
                    "json.{$index}" => 'Each result must be an object.',
                ]);
            }

            $entries[] = $this->normalizeEntry($row, (string) $index);
        }

        return $entries;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array{id: string, home: int, away: int}
     */
    public function normalizeEntry(array $row, string $key): array
    {
        $validator = Validator::make($row, [
            'id' => ['required'],
            'home' => ['sometimes', 'integer', 'min:0', 'max:30'],
            'away' => ['sometimes', 'integer', 'min:0', 'max:30'],
            'home_score' => ['sometimes', 'integer', 'min:0', 'max:30'],
            'away_score' => ['sometimes', 'integer', 'min:0', 'max:30'],
        ]);

        $validator->after(function ($validator) use ($row): void {
            $hasHome = array_key_exists('home', $row) || array_key_exists('home_score', $row);
            $hasAway = array_key_exists('away', $row) || array_key_exists('away_score', $row);

            if (! $hasHome || ! $hasAway) {
                $validator->errors()->add('scores', 'Each result needs home and away scores.');
            }
        });

        if ($validator->fails()) {
            throw ValidationException::withMessages(
                collect($validator->errors()->messages())
                    ->mapWithKeys(fn (array $messages, string $field): array => ["{$key}.{$field}" => $messages[0]])
                    ->all(),
            );
        }

        /** @var array<string, mixed> $validated */
        $validated = $validator->validated();

        return [
            'id' => (string) $validated['id'],
            'home' => (int) ($validated['home'] ?? $validated['home_score']),
            'away' => (int) ($validated['away'] ?? $validated['away_score']),
        ];
    }

    public function findByExternalId(string $externalId): Fixture
    {
        $fixture = Fixture::query()
            ->where('external_id', $externalId)
            ->with(['homeTeam', 'awayTeam'])
            ->first();

        if ($fixture === null) {
            throw ValidationException::withMessages([
                'fixture' => "Fixture [{$externalId}] was not found.",
            ]);
        }

        return $fixture;
    }

    public function record(Fixture $fixture, FixtureResult $result): Fixture
    {
        $this->assertValidScores($result);

        $winnerId = $this->resolveWinnerId($fixture, $result);

        $fixture->update([
            'status' => FixtureStatus::Final,
            ...$result->toAttributes(),
            'winner_team_id' => $winnerId,
        ]);

        $this->bumpCaches();

        return $fixture->fresh(['homeTeam', 'awayTeam']);
    }

    public function recordLive(Fixture $fixture, FixtureResult $result): Fixture
    {
        $this->assertValidScores($result);

        $fixture->update([
            'status' => FixtureStatus::Live,
            ...$result->toAttributes(),
            'winner_team_id' => null,
            'penalties_home' => null,
            'penalties_away' => null,
        ]);

        $this->bumpCaches();

        return $fixture->fresh(['homeTeam', 'awayTeam']);
    }

    public function describe(Fixture $fixture): string
    {
        $home = $fixture->homeTeam?->name ?? 'TBD';
        $away = $fixture->awayTeam?->name ?? 'TBD';

        return sprintf(
            'M%s — %s vs %s (%s)',
            $fixture->external_id,
            $home,
            $away,
            $fixture->stage->label(),
        );
    }

    private function resolveWinnerId(Fixture $fixture, FixtureResult $result): ?int
    {
        if ($result->winnerTeamId !== null) {
            return $result->winnerTeamId;
        }

        $homeTeamId = $fixture->home_team_id;
        $awayTeamId = $fixture->away_team_id;

        if ($homeTeamId === null || $awayTeamId === null) {
            return null;
        }

        if (
            $result->penaltiesHome !== null
            && $result->penaltiesAway !== null
            && $result->penaltiesHome !== $result->penaltiesAway
        ) {
            return $result->penaltiesHome > $result->penaltiesAway ? $homeTeamId : $awayTeamId;
        }

        $totalHome = $result->homeScore + ($result->extraTimeHome ?? 0);
        $totalAway = $result->awayScore + ($result->extraTimeAway ?? 0);

        if ($totalHome === $totalAway) {
            return null;
        }

        return $totalHome > $totalAway ? $homeTeamId : $awayTeamId;
    }

    private function assertValidScores(FixtureResult $result): void
    {
        foreach ([
            $result->homeScore,
            $result->awayScore,
            $result->extraTimeHome,
            $result->extraTimeAway,
            $result->penaltiesHome,
            $result->penaltiesAway,
        ] as $score) {
            if ($score === null) {
                continue;
            }

            if ($score < 0 || $score > 30) {
                throw ValidationException::withMessages([
                    'scores' => 'Scores must be between 0 and 30.',
                ]);
            }
        }

        if (
            $result->penaltiesHome !== null
            && $result->penaltiesAway !== null
            && $result->homeScore !== $result->awayScore
        ) {
            throw ValidationException::withMessages([
                'penalties' => 'Penalty shootout scores require a level regular-time score.',
            ]);
        }
    }

    private function bumpCaches(): void
    {
        $this->cache->bump('bracket');
        $this->cache->bump('predict');
    }
}
