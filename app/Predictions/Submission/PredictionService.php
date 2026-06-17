<?php

namespace App\Predictions\Submission;

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
        [$start, $end] = $this->watDayWindow($watDate);

        $marketIds = array_map(static fn (array $entry): int => $entry['fixture_market_id'], $entries);

        $markets = FixtureMarket::query()
            ->with(['fixture', 'market'])
            ->whereIn('id', $marketIds)
            ->get()
            ->keyBy('id');

        DB::transaction(function () use ($user, $entries, $bankerFixtureMarketId, $markets, $start, $end): void {
            foreach ($entries as $entry) {
                $fixtureMarket = $markets->get($entry['fixture_market_id']);

                if (! $fixtureMarket instanceof FixtureMarket) {
                    $this->fail('That market is no longer available.');
                }

                $this->assertBelongsToDay($fixtureMarket, $start, $end);
                $this->assertOpen($fixtureMarket);

                $value = $this->validator->validate($fixtureMarket, $entry['value']);

                Prediction::updateOrCreate(
                    ['user_id' => $user->id, 'fixture_market_id' => $fixtureMarket->id],
                    ['value' => $value],
                );
            }

            $this->reconcileBanker($user, $bankerFixtureMarketId, $start, $end);
        });

        $this->referrals->attemptCreditPendingForReferrer($user);
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
        $this->assertBelongsToDay($fixtureMarket, $start, $end);
        $this->assertOpen($fixtureMarket, 'banker');

        $prediction->update(['is_banker' => true]);
    }

    private function assertOpen(FixtureMarket $fixtureMarket, ?string $key = null): void
    {
        if (! $fixtureMarket->isOpenForPrediction()) {
            $this->fail('This market is locked — predictions closed before kickoff.', $key ?? "markets.{$fixtureMarket->id}");
        }
    }

    private function assertBelongsToDay(FixtureMarket $fixtureMarket, Carbon $start, Carbon $end): void
    {
        $kickoff = $fixtureMarket->fixture->kickoff_at;

        if ($kickoff->lessThan($start) || $kickoff->greaterThanOrEqualTo($end)) {
            $this->fail('That market is not part of this match day.');
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
