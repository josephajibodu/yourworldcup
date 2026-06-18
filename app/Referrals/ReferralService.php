<?php

namespace App\Referrals;

use App\Models\Referral;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReferralService
{
    /**
     * Credit the referrer once a referred user has made their own prediction
     * and the referrer has already made at least one prediction.
     */
    public function attemptCredit(User $referred): void
    {
        if ($referred->referred_by_id === null) {
            return;
        }

        if (! $referred->hasMadePrediction()) {
            return;
        }

        if (Referral::query()->where('referred_id', $referred->id)->exists()) {
            return;
        }

        $referrer = $referred->referrer;

        if ($referrer === null || $referrer->id === $referred->id) {
            return;
        }

        if (! $referrer->hasMadePrediction()) {
            return;
        }

        if ($this->dailyCapReached($referrer)) {
            return;
        }

        DB::transaction(function () use ($referrer, $referred): void {
            if (Referral::query()->where('referred_id', $referred->id)->lockForUpdate()->exists()) {
                return;
            }

            if ($this->dailyCapReached($referrer)) {
                return;
            }

            Referral::create([
                'referrer_id' => $referrer->id,
                'referred_id' => $referred->id,
                'points' => config('referrals.points_per_referral'),
                'wat_date' => $this->currentWatDate(),
                'credited_at' => now(),
            ]);
        });
    }

    /**
     * Retry pending credits for referred users who have already predicted —
     * called after the referrer submits their first prediction.
     */
    public function attemptCreditPendingForReferrer(User $referrer): void
    {
        if (! $referrer->hasMadePrediction()) {
            return;
        }

        User::query()
            ->where('referred_by_id', $referrer->id)
            ->whereHas('predictions')
            ->each(fn (User $referred) => $this->attemptCredit($referred));
    }

    public function dailyCapReached(User $referrer): bool
    {
        $count = Referral::query()
            ->where('referrer_id', $referrer->id)
            ->where('wat_date', $this->currentWatDate())
            ->count();

        return $count >= config('referrals.daily_cap');
    }

    private function currentWatDate(): string
    {
        return Carbon::now(config('predictions.timezone'))->toDateString();
    }
}
