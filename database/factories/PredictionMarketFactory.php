<?php

namespace Database\Factories;

use App\Enums\MarketInputType;
use App\Models\PredictionMarket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PredictionMarket>
 */
class PredictionMarketFactory extends Factory
{
    protected $model = PredictionMarket::class;

    public function definition(): array
    {
        return [
            'key' => fake()->unique()->slug(2),
            'name' => fake()->words(2, true),
            'description' => null,
            'input_type' => MarketInputType::SingleSelect,
            'options' => [['value' => 'home'], ['value' => 'draw'], ['value' => 'away']],
            'base_points' => 1,
            'scorer' => 'match_winner',
            'is_default' => false,
            'is_enabled' => true,
            'sort_order' => 0,
        ];
    }

    public function matchWinner(): static
    {
        return $this->state(fn (): array => [
            'key' => 'match_winner',
            'name' => 'Match winner',
            'input_type' => MarketInputType::SingleSelect,
            'options' => [['value' => 'home'], ['value' => 'draw'], ['value' => 'away']],
            'base_points' => 1,
            'scorer' => 'match_winner',
            'is_default' => true,
        ]);
    }

    public function exactScore(): static
    {
        return $this->state(fn (): array => [
            'key' => 'exact_score',
            'name' => 'Exact score',
            'input_type' => MarketInputType::Scoreline,
            'options' => null,
            'base_points' => 3,
            'scorer' => 'exact_score',
            'is_default' => false,
        ]);
    }
}
