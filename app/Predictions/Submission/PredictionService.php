<?php

namespace App\Predictions\Submission;

use App\Cache\TournamentCache;
use App\Models\FixtureMarket;
use App\Models\Prediction;
use App\Models\User;
use App\Referrals\ReferralService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PredictionService
{
    public function __construct(
        private MarketValueValidator $validator,
        private ReferralService $referrals,
        private PredictionVisibility $visibility,
        private TournamentCache $cache,
    ) {}

    /**
     * Persist a user's picks for a single WAT day.
     *
     * Each entry is upserted against its market after lock + ownership checks,
     * then the day's banker is reconciled so at most one prediction is flagged.
     *
     * @param  array<int, array{fixture_market_id: int, value: array<string, mixed>}>  $entries
     */
    public function submitDay(User $user, string $watDate, array $entries, ?int $bankerFixtureMarketId): void
    {
        if (! $this->visibility->isDateVisible($watDate)) {
            $this->fail('Predictions for that day are not open yet.');
        }

        $hadPredictions = $user->hasMadePrediction();

        [$start, $end] = $this->watDayWindow($watDate);

        $marketIds = array_map(static fn (array $entry): int => $entry['fixture_market_id'], $entries);

        $markets = FixtureMarket::query()
            ->with(['fixture', 'market'])
            ->whereIn('id', $marketIds)
            ->get()
            ->keyBy('id');

        DB::transaction(function () use ($user, $entries, $bankerFixtureMarketId, $markets, $start, $end): void {
            $submittedAt = now();
            $anyChanged = false;

            foreach ($entries as $entry) {
                $fixtureMarket = $markets->get($entry['fixture_market_id']);

                if (! $fixtureMarket instanceof FixtureMarket) {
                    $this->fail('That market is no longer available.');
                }

                $this->assertOpen($fixtureMarket);

                $value = $this->validator->validate($fixtureMarket, $entry['value']);

                $anyChanged = $this->persistPrediction($user, $fixtureMarket, $value) || $anyChanged;
            }

            if ($anyChanged) {
                Prediction::query()
                    ->where('user_id', $user->id)
                    ->whereHas('fixtureMarket.fixture', fn ($query) => $query->whereBetween('kickoff_at', [$start, $end]))
                    ->update(['submitted_at' => $submittedAt]);
            }

            $this->reconcileBanker($user, $bankerFixtureMarketId, $start, $end);
        });

        $this->referrals->attemptCredit($user);
        $this->referrals->attemptCreditPendingForReferrer($user);

        if (! $hadPredictions) {
            $this->cache->bump('leaderboard');
        }
    }

    /**
     * Clear any existing banker on the day, then flag the chosen prediction.
     */
    private function reconcileBanker(User $user, ?int $bankerFixtureMarketId, Carbon $start, Carbon $end): void
    {
        Prediction::query()
            ->where('user_id', $user->id)
            ->whereHas('fixtureMarket.fixture', fn ($query) => $query->whereBetween('kickoff_at', [$start, $end]))
            ->update(['is_banker' => false]);

        if ($bankerFixtureMarketId === null) {
            return;
        }

        $prediction = Prediction::query()
            ->with('fixtureMarket.fixture')
            ->where('user_id', $user->id)
            ->where('fixture_market_id', $bankerFixtureMarketId)
            ->first();

        if ($prediction === null) {
            $this->fail('You can only banker a pick you have made today.', 'banker');
        }

        $fixtureMarket = $prediction->fixtureMarket;
        $this->assertOpen($fixtureMarket, 'banker');

        $prediction->update(['is_banker' => true]);
    }

    /**
     * @param  array<string, mixed>  $value
     */
    private function persistPrediction(User $user, FixtureMarket $fixtureMarket, array $value): bool
    {
        $prediction = Prediction::query()->firstOrNew([
            'user_id' => $user->id,
            'fixture_market_id' => $fixtureMarket->id,
        ]);

        $changed = ! $prediction->exists || $prediction->value != $value;

        $prediction->value = $value;

        if (! $prediction->exists) {
            $prediction->submitted_at = now();
        }

        $prediction->save();

        return $changed;
    }

    private function assertOpen(FixtureMarket $fixtureMarket, ?string $key = null): void
    {
        if (! $fixtureMarket->isOpenForPrediction()) {
            $this->fail('This market is locked — predictions closed before kickoff.', $key ?? "markets.{$fixtureMarket->id}");
        }
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function watDayWindow(string $watDate): array
    {
        $start = Carbon::parse($watDate, config('predictions.timezone'))->startOfDay();

        return [$start->copy()->utc(), $start->copy()->addDay()->utc()];
    }

    /**
     * @return never
     */
    private function fail(string $message, string $key = 'predictions'): void
    {
        throw ValidationException::withMessages([$key => $message]);
    }
}
