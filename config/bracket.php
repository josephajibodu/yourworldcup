<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Knockout feed map
    |--------------------------------------------------------------------------
    |
    | Maps each knockout match (by its source id) to the two matches that feed it:
    | [home feeder id, away feeder id]. Winners advance to the Final (104); the two
    | semi-final losers meet in the third-place play-off (103).
    |
    | Round-of-32 entry slots (73–88) are imported into `bracket_slots` from the
    | match labels in `database/data/football.matches.json` (1A, 2B, 3rd D/E/I/J/L, …).
    | `BracketResolverService` fills them after group or knockout results land.
    | Swap `BestThirdQualifier` in the container for FIFA-accurate third-place ranking.
    | Knockout pathway edges prefer `bracket_slots`; this map is a fallback only.
    |
    */
    'feeders' => [
        // Round of 16
        89 => [74, 77],
        90 => [73, 75],
        91 => [76, 78],
        92 => [79, 80],
        93 => [83, 84],
        94 => [81, 82],
        95 => [86, 88],
        96 => [85, 87],
        // Quarter-finals
        97 => [89, 90],
        98 => [93, 94],
        99 => [91, 92],
        100 => [95, 96],
        // Semi-finals
        101 => [97, 98],
        102 => [99, 100],
        // Third-place play-off (semi-final losers) and Final (semi-final winners)
        103 => [101, 102],
        104 => [101, 102],
    ],
];
