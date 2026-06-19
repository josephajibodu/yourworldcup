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

    public function record(Fixture $fixture, int $homeScore, int $awayScore): Fixture
    {
        if ($homeScore < 0 || $homeScore > 30 || $awayScore < 0 || $awayScore > 30) {
            throw ValidationException::withMessages([
                'scores' => 'Scores must be between 0 and 30.',
            ]);
        }

        $homeTeamId = $fixture->home_team_id;
        $awayTeamId = $fixture->away_team_id;

        $winnerId = null;

        if ($homeTeamId !== null && $awayTeamId !== null && $homeScore !== $awayScore) {
            $winnerId = $homeScore > $awayScore ? $homeTeamId : $awayTeamId;
        }

        $fixture->update([
            'status' => FixtureStatus::Final,
            'home_score' => $homeScore,
            'away_score' => $awayScore,
            'winner_team_id' => $winnerId,
        ]);

        $this->cache->bump('bracket');
        $this->cache->bump('predict');

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
}
