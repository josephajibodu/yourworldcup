<?php

namespace App\FootballData;

use App\Cache\TournamentCache;
use App\Enums\FixtureStatus;
use App\Enums\ResultDuration;
use App\Fixtures\FixtureResult;
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
        $fixtureResult = $this->extractProviderResult($fixture, $match, finished: true);

        if ($fixtureResult === null) {
            $result->skipped++;

            return;
        }

        $resultChanged = $fixture->status === FixtureStatus::Final
            && ! $fixtureResult->matchesFixture($fixture);

        if ($fixture->status === FixtureStatus::Final && $fixtureResult->matchesFixture($fixture)) {
            $result->skipped++;

            return;
        }

        $updated = $this->recorder->record($fixture, $fixtureResult);
        $result->updated++;

        if ($settle) {
            $result->settledPredictions += $this->settlement->settleFixture($updated, includeSettled: $resultChanged);
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

        $fixtureResult = $this->extractProviderResult($fixture, $match, finished: false);

        if ($fixtureResult === null) {
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

        if ($this->alreadyRecordedLive($fixture, $fixtureResult)) {
            $result->skipped++;

            return;
        }

        $this->recorder->recordLive($fixture, $fixtureResult);
        $result->updated++;
    }

    /**
     * @param  array<string, mixed>  $match
     */
    private function extractProviderResult(
        Fixture $fixture,
        array $match,
        bool $finished,
    ): ?FixtureResult {
        $score = $match['score'] ?? [];
        $regularScores = $this->extractPeriodScores($score, 'regularTime')
            ?? $this->extractPeriodScores($score, 'fullTime');

        if ($regularScores === null) {
            return null;
        }

        [$homeScore, $awayScore] = $regularScores;
        $extraTime = $this->extractPeriodScores($score, 'extraTime');
        $penalties = $finished ? $this->extractPeriodScores($score, 'penalties') : null;
        $duration = ResultDuration::fromProvider($score['duration'] ?? null);

        if (! $finished && strtoupper((string) ($match['status'] ?? '')) === 'PENALTY_SHOOTOUT') {
            $duration = ResultDuration::Penalties;
            $penalties = null;
        }

        $winnerTeamId = $homeScore === $awayScore
            ? $this->extractProviderWinnerTeamId($fixture, $match)
            : null;

        return new FixtureResult(
            homeScore: $homeScore,
            awayScore: $awayScore,
            winnerTeamId: $winnerTeamId,
            extraTimeHome: $extraTime[0] ?? null,
            extraTimeAway: $extraTime[1] ?? null,
            penaltiesHome: $penalties[0] ?? null,
            penaltiesAway: $penalties[1] ?? null,
            resultDuration: $duration,
        );
    }

    /**
     * @param  array<string, mixed>  $score
     * @return array{0: int, 1: int}|null
     */
    private function extractPeriodScores(array $score, string $period): ?array
    {
        $homeScore = $score[$period]['home'] ?? null;
        $awayScore = $score[$period]['away'] ?? null;

        if (! is_numeric($homeScore) || ! is_numeric($awayScore)) {
            return null;
        }

        return [(int) $homeScore, (int) $awayScore];
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

    private function alreadyRecordedLive(Fixture $fixture, FixtureResult $result): bool
    {
        return $fixture->status === FixtureStatus::Live
            && $result->homeScore === $fixture->home_score
            && $result->awayScore === $fixture->away_score
            && $result->extraTimeHome === $fixture->extra_time_home
            && $result->extraTimeAway === $fixture->extra_time_away
            && $fixture->penalties_home === null
            && $fixture->penalties_away === null;
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
}
