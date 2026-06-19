<?php

namespace App\FootballData;

use App\Enums\FixtureStatus;
use App\Fixtures\FixtureResultRecorder;
use App\Models\Fixture;
use App\Predictions\Settlement\SettlementService;
use Illuminate\Support\Carbon;

class FootballDataScoreSyncService
{
    /**
     * @var list<string>
     */
    private const LIVE_PROVIDER_STATUSES = [
        'IN_PLAY',
        'PAUSED',
        'LIVE',
        'EXTRATIME',
        'PENALTY_SHOOTOUT'
    ];

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
        ]);

        return $this->applyMatches($matches, $settle);
    }

    public function syncAllPlayed(bool $settle = false): FootballDataSyncResult
    {
        $matches = $this->api->competitionMatches([
            'status' => 'FINISHED',
        ]);

        return $this->applyMatches($matches, $settle);
    }

    /**
     * @param  list<array<string, mixed>>  $matches
     */
    public function applyMatches(array $matches, bool $settle): FootballDataSyncResult
    {
        $result = new FootballDataSyncResult(fetched: count($matches));

        foreach ($matches as $match) {
            $this->applyMatch($match, $settle, $result);
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $match
     */
    private function applyMatch(array $match, bool $settle, FootballDataSyncResult $result): void
    {
        $providerStatus = strtoupper((string) ($match['status'] ?? ''));

        $fixture = Fixture::findByProviderMatch(
            $this->provider,
            (string) $match['id'],
        );

        if ($fixture === null) {
            $result->unlinked++;

            return;
        }

        if ($providerStatus === 'FINISHED' || $providerStatus === 'AWARDED') {
            $this->applyFinishedMatch($fixture, $match, $settle, $result);

            return;
        }

        if ($this->isLiveProviderStatus($providerStatus)) {
            $this->applyLiveMatch($fixture, $result);

            return;
        }

        $result->skipped++;
    }

    /**
     * @param  array<string, mixed>  $match
     */
    private function applyFinishedMatch(
        Fixture $fixture,
        array $match,
        bool $settle,
        FootballDataSyncResult $result,
    ): void {
        $homeScore = $match['score']['fullTime']['home'] ?? null;
        $awayScore = $match['score']['fullTime']['away'] ?? null;

        if (! is_numeric($homeScore) || ! is_numeric($awayScore)) {
            $result->skipped++;

            return;
        }

        $homeScore = (int) $homeScore;
        $awayScore = (int) $awayScore;

        if ($this->alreadyRecorded($fixture, $homeScore, $awayScore)) {
            $result->skipped++;

            return;
        }

        $updated = $this->recorder->record($fixture, $homeScore, $awayScore);
        $result->updated++;

        if ($settle) {
            $result->settledPredictions += $this->settlement->settleFixture($updated);
        }
    }

    private function applyLiveMatch(Fixture $fixture, FootballDataSyncResult $result): void
    {
        if (in_array($fixture->status, [FixtureStatus::Final, FixtureStatus::Void], true)) {
            $result->skipped++;

            return;
        }

        if ($fixture->status === FixtureStatus::Live) {
            $result->skipped++;

            return;
        }

        $fixture->update(['status' => FixtureStatus::Live]);
        $result->updated++;
    }

    private function isLiveProviderStatus(string $providerStatus): bool
    {
        return in_array($providerStatus, self::LIVE_PROVIDER_STATUSES, true);
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
