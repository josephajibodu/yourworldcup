<?php

namespace App\Predictions\Markets;

use App\Enums\MarketInputType;
use App\Models\Fixture;
use App\Models\FixtureMarket;
use App\Models\PredictionMarket;

class PlayerMarketAttachmentService
{
    /**
     * @param  list<array{
     *     key: string,
     *     name: string,
     *     description: string,
     *     input_type: MarketInputType,
     *     base_points: int,
     *     sort_order: int
     * }>  $definitions
     */
    public function attach(Fixture $fixture, array $definitions): int
    {
        $attached = 0;

        foreach ($definitions as $definition) {
            $market = PredictionMarket::updateOrCreate(
                ['key' => $definition['key']],
                [
                    'name' => $definition['name'],
                    'description' => $definition['description'],
                    'input_type' => $definition['input_type'],
                    'options' => null,
                    'base_points' => $definition['base_points'],
                    'scorer' => $definition['key'],
                    'is_default' => false,
                    'is_enabled' => false,
                    'sort_order' => $definition['sort_order'],
                ],
            );

            FixtureMarket::firstOrCreate([
                'fixture_id' => $fixture->id,
                'prediction_market_id' => $market->id,
            ]);

            $attached++;
        }

        return $attached;
    }
}
