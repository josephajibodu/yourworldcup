<?php

namespace Database\Factories;

use App\Models\Referral;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Referral>
 */
class ReferralFactory extends Factory
{
    protected $model = Referral::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $watDate = Carbon::now(config('predictions.timezone'))->toDateString();

        return [
            'referrer_id' => User::factory(),
            'referred_id' => User::factory(),
            'points' => config('referrals.points_per_referral'),
            'wat_date' => $watDate,
            'credited_at' => now(),
        ];
    }
}
