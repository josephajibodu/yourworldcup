<?php

namespace App\Predictions\Markets;

use App\Enums\MarketInputType;

final class M102PlayerMarkets
{
    public const string MATCH_NUMBER = '102';

    /** @var list<string> */
    public const array EXPECTED_TEAMS = ['Argentina', 'England'];

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
                'key' => 'player_messi_score_or_assist',
                'name' => 'Messi to score or assist',
                'description' => 'Lionel Messi (Argentina)',
                'input_type' => MarketInputType::Boolean,
                'base_points' => 1,
                'sort_order' => 201,
            ],
            [
                'key' => 'player_kane_score',
                'name' => 'Kane to score',
                'description' => 'Harry Kane (England)',
                'input_type' => MarketInputType::Boolean,
                'base_points' => 1,
                'sort_order' => 202,
            ],
            [
                'key' => 'player_rice_assist',
                'name' => 'Rice to assist',
                'description' => 'Declan Rice (England)',
                'input_type' => MarketInputType::Boolean,
                'base_points' => 1,
                'sort_order' => 203,
            ],
            [
                'key' => 'player_saka_score_or_assist',
                'name' => 'Saka to score or assist',
                'description' => 'Bukayo Saka (England)',
                'input_type' => MarketInputType::Boolean,
                'base_points' => 1,
                'sort_order' => 204,
            ],
            [
                'key' => 'player_bellingham_score_or_assist',
                'name' => 'Bellingham to score or assist',
                'description' => 'Jude Bellingham (England)',
                'input_type' => MarketInputType::Boolean,
                'base_points' => 1,
                'sort_order' => 205,
            ],
            [
                'key' => 'player_rashford_score',
                'name' => 'Rashford to score',
                'description' => 'Marcus Rashford (England)',
                'input_type' => MarketInputType::Boolean,
                'base_points' => 1,
                'sort_order' => 206,
            ],
            [
                'key' => 'player_fernandez_score',
                'name' => 'Fernández to score',
                'description' => 'Enzo Fernández (Argentina)',
                'input_type' => MarketInputType::Boolean,
                'base_points' => 1,
                'sort_order' => 207,
            ],
            [
                'key' => 'player_de_paul_score',
                'name' => 'De Paul to score',
                'description' => 'Rodrigo De Paul (Argentina)',
                'input_type' => MarketInputType::Boolean,
                'base_points' => 1,
                'sort_order' => 208,
            ],
            [
                'key' => 'player_alvarez_score',
                'name' => 'Álvarez to score',
                'description' => 'Julián Álvarez (Argentina)',
                'input_type' => MarketInputType::Boolean,
                'base_points' => 1,
                'sort_order' => 209,
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
