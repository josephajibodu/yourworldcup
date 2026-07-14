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

    public function toQualify(): static
    {
        return $this->state(fn (): array => [
            'key' => 'to_qualify',
            'name' => 'To qualify',
            'input_type' => MarketInputType::SingleSelect,
            'options' => [
                ['value' => 'home', 'label' => 'Home'],
                ['value' => 'away', 'label' => 'Away'],
            ],
            'base_points' => 1,
            'scorer' => 'to_qualify',
            'is_default' => false,
        ]);
    }

    public function penaltyShootout(): static
    {
        return $this->state(fn (): array => [
            'key' => 'penalty_shootout',
            'name' => 'Penalty shootout',
            'input_type' => MarketInputType::Boolean,
            'options' => null,
            'base_points' => 1,
            'scorer' => 'penalty_shootout',
            'is_default' => false,
        ]);
    }

    public function homeCleanSheet(): static
    {
        return $this->state(fn (): array => [
            'key' => 'home_clean_sheet',
            'name' => 'Home team clean sheet',
            'input_type' => MarketInputType::Boolean,
            'options' => null,
            'base_points' => 1,
            'scorer' => 'home_clean_sheet',
            'is_default' => false,
        ]);
    }

    public function awayCleanSheet(): static
    {
        return $this->state(fn (): array => [
            'key' => 'away_clean_sheet',
            'name' => 'Away team clean sheet',
            'input_type' => MarketInputType::Boolean,
            'options' => null,
            'base_points' => 1,
            'scorer' => 'away_clean_sheet',
            'is_default' => false,
        ]);
    }

    public function lastGoal(): static
    {
        return $this->state(fn (): array => [
            'key' => 'last_goal',
            'name' => 'Last goal',
            'input_type' => MarketInputType::SingleSelect,
            'options' => [
                ['value' => 'home', 'label' => 'Home'],
                ['value' => 'none', 'label' => 'None'],
                ['value' => 'away', 'label' => 'Away'],
            ],
            'base_points' => 2,
            'scorer' => 'last_goal',
            'is_default' => false,
        ]);
    }

    public function highestBooking(): static
    {
        return $this->state(fn (): array => [
            'key' => 'highest_booking',
            'name' => 'Highest booking',
            'input_type' => MarketInputType::SingleSelect,
            'options' => [
                ['value' => 'home', 'label' => 'Home'],
                ['value' => 'draw', 'label' => 'Draw'],
                ['value' => 'away', 'label' => 'Away'],
            ],
            'base_points' => 2,
            'scorer' => 'highest_booking',
            'is_default' => false,
        ]);
    }
}
