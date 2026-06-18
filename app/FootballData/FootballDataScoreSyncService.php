<?php

namespace App\FootballData;

use App\Enums\FixtureStatus;
use App\Fixtures\FixtureResultRecorder;
use App\Models\Fixture;
use App\Predictions\Settlement\SettlementService;
use Illuminate\Support\Carbon;

class FootballDataScoreSyncService
{
    public function __construct(
        private FootballDataApiClient $api,
        private FixtureResultRecorder $recorder,
        private SettlementService $settlement,
        private string $provider,
        private string $timezone,
    ) {}

    public static function fromConfig(
        ?FootballDataApiClient $api = null,
        ?FixtureResultRecorder $recorder = null,
        ?SettlementService $settlement = null,
    ): self {
        return new self(
            api: $api ?? FootballDataApiClient::fromConfig(),
            recorder: $recorder ?? app(FixtureResultRecorder::class),
            settlement: $settlement ?? app(SettlementService::class),
            provider: (string) config('football_data.provider'),
            timezone: (string) config('predictions.timezone'),
        );
    }

    public function syncRecent(bool $settle = true): FootballDataSyncResult
    {
        [$dateFrom, $dateTo] = $this->recentWatDateRange();

        $matches = $this->api->competitionMatches([
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'status' => 'FINISHED',
        ]);

        return $this->applyFinishedMatches($matches, $settle);
    }

    public function syncAllPlayed(bool $settle = false): FootballDataSyncResult
    {
        $matches = $this->api->competitionMatches([
            'status' => 'FINISHED',
        ]);

        return $this->applyFinishedMatches($matches, $settle);
    }

    /**
     * @param  list<array<string, mixed>>  $matches
     */
    public function applyFinishedMatches(array $matches, bool $settle): FootballDataSyncResult
    {
        $result = new FootballDataSyncResult(fetched: count($matches));

        foreach ($matches as $match) {
            if (($match['status'] ?? '') !== 'FINISHED') {
                continue;
            }

            $homeScore = $match['score']['fullTime']['home'] ?? null;
            $awayScore = $match['score']['fullTime']['away'] ?? null;

            if (! is_numeric($homeScore) || ! is_numeric($awayScore)) {
                $result->skipped++;

                continue;
            }

            $homeScore = (int) $homeScore;
            $awayScore = (int) $awayScore;

            $fixture = Fixture::findByProviderMatch(
                $this->provider,
                (string) $match['id'],
            );

            if ($fixture === null) {
                $result->unlinked++;

                continue;
            }

            if ($this->alreadyRecorded($fixture, $homeScore, $awayScore)) {
                $result->skipped++;

                continue;
            }

            $updated = $this->recorder->record($fixture, $homeScore, $awayScore);
            $result->updated++;

            if ($settle) {
                $result->settledPredictions += $this->settlement->settleFixture($updated);
            }
        }

        return $result;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function recentWatDateRange(): array
    {
        $today = Carbon::now($this->timezone)->toDateString();
        $yesterday = Carbon::now($this->timezone)->subDay()->toDateString();

        return [$yesterday, $today];
    }

    private function alreadyRecorded(Fixture $fixture, int $homeScore, int $awayScore): bool
    {
        return $fixture->status === FixtureStatus::Final
            && $fixture->home_score === $homeScore
            && $fixture->away_score === $awayScore;
    }
}
