<?php

namespace App\Predictions\Markets;

use App\Enums\MarketInputType;

final class SpecialPlayerMarkets
{
    /**
     * @return list<class-string>
     */
    private static function catalogues(): array
    {
        return [
            M101PlayerMarkets::class,
            M102PlayerMarkets::class,
        ];
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_merge(...array_map(
            static fn (string $catalogue): array => $catalogue::keys(),
            self::catalogues(),
        ));
    }

    /**
     * @return list<string>
     */
    public static function keysForMatch(?string $matchNumber): array
    {
        return array_column(self::definitionsForMatch($matchNumber), 'key');
    }

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
    public static function definitionsForMatch(?string $matchNumber): array
    {
        return match ($matchNumber) {
            M101PlayerMarkets::MATCH_NUMBER => M101PlayerMarkets::definitions(),
            M102PlayerMarkets::MATCH_NUMBER => M102PlayerMarkets::definitions(),
            default => [],
        };
    }

    /**
     * @return list<string>
     */
    public static function expectedTeamsForMatch(string $matchNumber): array
    {
        return match ($matchNumber) {
            M101PlayerMarkets::MATCH_NUMBER => M101PlayerMarkets::EXPECTED_TEAMS,
            M102PlayerMarkets::MATCH_NUMBER => M102PlayerMarkets::EXPECTED_TEAMS,
            default => [],
        };
    }
}
