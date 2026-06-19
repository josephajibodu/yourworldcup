<?php

namespace Database\Factories;

use App\Models\FixtureMarket;
use App\Models\Prediction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Prediction>
 */
class PredictionFactory extends Factory
{
    protected $model = Prediction::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'fixture_market_id' => FixtureMarket::factory(),
            'value' => ['selected' => 'home'],
            'is_banker' => false,
            'submitted_at' => now(),
        ];
    }

    public function banker(): static
    {
        return $this->state(fn (): array => ['is_banker' => true]);
    }
}
