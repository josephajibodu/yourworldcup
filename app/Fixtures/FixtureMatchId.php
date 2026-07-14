<?php

namespace App\Fixtures;

final class FixtureMatchId
{
    public static function normalize(string $match): string
    {
        return (string) (int) preg_replace('/^[Mm]/', '', trim($match));
    }
}
