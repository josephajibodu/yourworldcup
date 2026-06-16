<?php

namespace Database\Seeders;

use App\Enums\MarketInputType;
use App\Models\PredictionMarket;
use Illuminate\Database\Seeder;

class PredictionMarketSeeder extends Seeder
{
    public function run(): void
    {
        $markets = [
            [
                'key' => 'match_winner',
                'name' => 'Match winner',
                'description' => 'Who wins the match?',
                'input_type' => MarketInputType::SingleSelect,
                'options' => [
                    ['value' => 'home', 'label' => 'Home'],
                    ['value' => 'draw', 'label' => 'Draw'],
                    ['value' => 'away', 'label' => 'Away'],
                ],
                'base_points' => 1,
                'scorer' => 'match_winner',
                'is_default' => true,
                'is_enabled' => true,
                'sort_order' => 1,
            ],
            [
                'key' => 'exact_score',
                'name' => 'Exact score',
                'description' => 'Predict the exact full-time scoreline.',
                'input_type' => MarketInputType::Scoreline,
                'options' => null,
                'base_points' => 3,
                'scorer' => 'exact_score',
                'is_default' => false,
                'is_enabled' => true,
                'sort_order' => 2,
            ],
        ];

        foreach ($markets as $market) {
            PredictionMarket::updateOrCreate(['key' => $market['key']], $market);
        }
    }
}
