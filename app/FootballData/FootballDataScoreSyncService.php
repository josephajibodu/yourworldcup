<?php

namespace App\FootballData;

use App\Cache\TournamentCache;
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
        'PENALTY_SHOOTOUT',
    ];

    public function __construct(
        private FootballDataApiClient $api,
        private FixtureResultRecorder $recorder,
        private SettlementService $settlement,
        private TournamentCache $cache,
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
            cache: app(TournamentCache::class),
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
            $this->applyLiveMatch($fixture, $match, $result);

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
        $scores = $this->extractSettlementScores($match);

        if ($scores === null) {
            $result->skipped++;

            return;
        }

        [$homeScore, $awayScore] = $scores;

        $winnerTeamId = $homeScore === $awayScore
            ? $this->extractProviderWinnerTeamId($fixture, $match)
            : null;

        $scoreCorrection = $fixture->status === FixtureStatus::Final
            && ($fixture->home_score !== $homeScore || $fixture->away_score !== $awayScore);

        if ($this->alreadyRecorded($fixture, $homeScore, $awayScore, $winnerTeamId)) {
            $result->skipped++;

            return;
        }

        $updated = $this->recorder->record($fixture, $homeScore, $awayScore, $winnerTeamId);
        $result->updated++;

        if ($settle) {
            $result->settledPredictions += $this->settlement->settleFixture($updated, includeSettled: $scoreCorrection);
        }
    }

    /**
     * @param  array<string, mixed>  $match
     */
    private function applyLiveMatch(
        Fixture $fixture,
        array $match,
        FootballDataSyncResult $result,
    ): void {
        if (in_array($fixture->status, [FixtureStatus::Final, FixtureStatus::Void], true)) {
            $result->skipped++;

            return;
        }

        $scores = $this->extractProviderScores($match);

        if ($scores === null) {
            if ($fixture->status === FixtureStatus::Live) {
                $result->skipped++;

                return;
            }

            $fixture->update(['status' => FixtureStatus::Live]);
            $this->cache->bump('bracket');
            $this->cache->bump('predict');
            $result->updated++;

            return;
        }

        [$homeScore, $awayScore] = $scores;

        if ($this->alreadyRecordedLive($fixture, $homeScore, $awayScore)) {
            $result->skipped++;

            return;
        }

        $this->recorder->recordLive($fixture, $homeScore, $awayScore);
        $result->updated++;
    }

    /**
     * @param  array<string, mixed>  $match
     * @return array{0: int, 1: int}|null
     */
    private function extractProviderScores(array $match): ?array
    {
        return $this->extractSettlementScores($match);
    }

    /**
     * Predictions settle on regular-time scores. Prefer regularTime when the
     * provider supplies it (knockout extra time / penalties), otherwise fall
     * back to fullTime for standard 90-minute finishes.
     *
     * @param  array<string, mixed>  $match
     * @return array{0: int, 1: int}|null
     */
    private function extractSettlementScores(array $match): ?array
    {
        $score = $match['score'] ?? [];

        foreach (['regularTime', 'fullTime'] as $period) {
            $homeScore = $score[$period]['home'] ?? null;
            $awayScore = $score[$period]['away'] ?? null;

            if (is_numeric($homeScore) && is_numeric($awayScore)) {
                return [(int) $homeScore, (int) $awayScore];
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $match
     */
    private function extractProviderWinnerTeamId(Fixture $fixture, array $match): ?int
    {
        $winner = strtoupper((string) ($match['score']['winner'] ?? ''));

        return match ($winner) {
            'HOME_TEAM' => $fixture->home_team_id,
            'AWAY_TEAM' => $fixture->away_team_id,
            default => null,
        };
    }

    private function alreadyRecordedLive(Fixture $fixture, int $homeScore, int $awayScore): bool
    {
        return $fixture->status === FixtureStatus::Live
            && $fixture->home_score === $homeScore
            && $fixture->away_score === $awayScore;
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

    private function alreadyRecorded(
        Fixture $fixture,
        int $homeScore,
        int $awayScore,
        ?int $winnerTeamId = null,
    ): bool {
        return $fixture->status === FixtureStatus::Final
            && $fixture->home_score === $homeScore
            && $fixture->away_score === $awayScore
            && $fixture->winner_team_id === $winnerTeamId;
    }
}
