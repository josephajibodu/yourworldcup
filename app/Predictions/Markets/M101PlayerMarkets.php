<?php

namespace App\Predictions\Markets;

use App\Enums\MarketInputType;

final class M101PlayerMarkets
{
    public const string MATCH_NUMBER = '101';

    /** @var list<string> */
    public const array EXPECTED_TEAMS = ['France', 'Spain'];

    /**
     * @return list<array{
     *     key: string,
     *     name: string,
     *     description: string,
     *     input_type: MarketInputType,
     *     base_points: int,
     *     sort_order: int
     * }>
     */
    public static function definitions(): array
    {
        return [
            [
                'key' => 'player_mbappe_score',
                'name' => 'Mbappé to score',
                'description' => 'Kylian Mbappé (France)',
                'input_type' => MarketInputType::Boolean,
                'base_points' => 1,
                'sort_order' => 101,
            ],
            [
                'key' => 'player_dembele_score',
                'name' => 'Dembélé to score',
                'description' => 'Ousmane Dembélé (France)',
                'input_type' => MarketInputType::Boolean,
                'base_points' => 1,
                'sort_order' => 102,
            ],
            [
                'key' => 'player_yamal_score',
                'name' => 'Yamal to score',
                'description' => 'Lamine Yamal (Spain)',
                'input_type' => MarketInputType::Boolean,
                'base_points' => 1,
                'sort_order' => 103,
            ],
            [
                'key' => 'player_torres_score',
                'name' => 'Torres to score',
                'description' => 'Ferran Torres (Spain)',
                'input_type' => MarketInputType::Boolean,
                'base_points' => 1,
                'sort_order' => 104,
            ],
            [
                'key' => 'player_olise_assist',
                'name' => 'Olise to assist',
                'description' => 'Michael Olise (France)',
                'input_type' => MarketInputType::Boolean,
                'base_points' => 1,
                'sort_order' => 105,
            ],
            [
                'key' => 'player_merino_score',
                'name' => 'Merino to score',
                'description' => 'Mikel Merino (Spain)',
                'input_type' => MarketInputType::Boolean,
                'base_points' => 1,
                'sort_order' => 106,
            ],
            [
                'key' => 'player_olmo_assist',
                'name' => 'Olmo to assist',
                'description' => 'Dani Olmo (Spain)',
                'input_type' => MarketInputType::Boolean,
                'base_points' => 1,
                'sort_order' => 107,
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_column(self::definitions(), 'key');
    }
}
