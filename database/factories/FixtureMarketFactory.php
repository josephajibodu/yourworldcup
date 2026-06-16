<?php

namespace Database\Factories;

use App\Enums\MarketStatus;
use App\Models\Fixture;
use App\Models\FixtureMarket;
use App\Models\PredictionMarket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FixtureMarket>
 */
class FixtureMarketFactory extends Factory
{
    protected $model = FixtureMarket::class;

    public function definition(): array
    {
        return [
            'fixture_id' => Fixture::factory(),
            'prediction_market_id' => PredictionMarket::factory(),
            'is_enabled' => true,
            'base_points' => null,
            'lock_at' => null,
            'options' => null,
            'settlement_value' => null,
            'status' => MarketStatus::Open,
        ];
    }

    /**
     * @param  array<string, mixed>  $settlement
     */
    public function settled(array $settlement): static
    {
        return $this->state(fn (): array => [
            'status' => MarketStatus::Settled,
            'settlement_value' => $settlement,
            'settled_at' => now(),
        ]);
    }
}
