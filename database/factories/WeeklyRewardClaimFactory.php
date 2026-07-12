<?php

namespace Database\Factories;

use App\Enums\RewardPreference;
use App\Models\User;
use App\Models\WeeklyRewardClaim;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WeeklyRewardClaim>
 */
class WeeklyRewardClaimFactory extends Factory
{
    protected $model = WeeklyRewardClaim::class;

    public function definition(): array
    {
        return [
            'week_start' => now()->startOfWeek()->toDateString(),
            'user_id' => User::factory(),
            'reward_position' => null,
            'leaderboard_rank' => 1,
            'preference' => RewardPreference::Airtime,
            'passed_on' => false,
            'pass_on_message' => null,
        ];
    }

    public function passedOn(?string $message = null): static
    {
        return $this->state(fn (): array => [
            'preference' => null,
            'passed_on' => true,
            'pass_on_message' => $message,
        ]);
    }
}
