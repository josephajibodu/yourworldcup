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
            [
                'key' => 'to_qualify',
                'name' => 'To qualify',
                'description' => 'Which team advances from this match?',
                'input_type' => MarketInputType::SingleSelect,
                'options' => [
                    ['value' => 'home', 'label' => 'Home'],
                    ['value' => 'away', 'label' => 'Away'],
                ],
                'base_points' => 1,
                'scorer' => 'to_qualify',
                'is_default' => false,
                'is_enabled' => true,
                'sort_order' => 3,
            ],
            [
                'key' => 'penalty_shootout',
                'name' => 'Penalty shootout',
                'description' => 'Will the match go to a penalty shootout?',
                'input_type' => MarketInputType::Boolean,
                'options' => null,
                'base_points' => 1,
                'scorer' => 'penalty_shootout',
                'is_default' => false,
                'is_enabled' => true,
                'sort_order' => 4,
            ],
            [
                'key' => 'home_clean_sheet',
                'name' => 'Home team clean sheet',
                'description' => 'Will the home team keep a clean sheet in regular time?',
                'input_type' => MarketInputType::Boolean,
                'options' => null,
                'base_points' => 1,
                'scorer' => 'home_clean_sheet',
                'is_default' => false,
                'is_enabled' => true,
                'sort_order' => 5,
            ],
            [
                'key' => 'away_clean_sheet',
                'name' => 'Away team clean sheet',
                'description' => 'Will the away team keep a clean sheet in regular time?',
                'input_type' => MarketInputType::Boolean,
                'options' => null,
                'base_points' => 1,
                'scorer' => 'away_clean_sheet',
                'is_default' => false,
                'is_enabled' => true,
                'sort_order' => 6,
            ],
            [
                'key' => 'last_goal',
                'name' => 'Last goal',
                'description' => 'Which team scores the last goal of the match?',
                'input_type' => MarketInputType::SingleSelect,
                'options' => [
                    ['value' => 'home', 'label' => 'Home'],
                    ['value' => 'none', 'label' => 'None'],
                    ['value' => 'away', 'label' => 'Away'],
                ],
                'base_points' => 2,
                'scorer' => 'last_goal',
                'is_default' => false,
                'is_enabled' => true,
                'sort_order' => 7,
            ],
            [
                'key' => 'highest_booking',
                'name' => 'Highest booking',
                'description' => 'Which team receives more cards?',
                'input_type' => MarketInputType::SingleSelect,
                'options' => [
                    ['value' => 'home', 'label' => 'Home'],
                    ['value' => 'draw', 'label' => 'Draw'],
                    ['value' => 'away', 'label' => 'Away'],
                ],
                'base_points' => 2,
                'scorer' => 'highest_booking',
                'is_default' => false,
                'is_enabled' => true,
                'sort_order' => 8,
            ],
        ];

        foreach ($markets as $market) {
            PredictionMarket::updateOrCreate(['key' => $market['key']], $market);
        }
    }
}
