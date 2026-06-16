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
    | Round-of-32 entry slots (73–88) come from group positions (1A, 2B, best thirds).
    | The data export ships knockout teams as "0" with no seeding map, so those slots
    | render as TBD until the official group->R32 assignment is provided. The R16 pairs
    | for matches 89–92 are a best-effort placeholder — verify against the official
    | bracket before launch.
    |
    */
    'feeders' => [
        // Round of 16
        89 => [73, 74],
        90 => [75, 76],
        91 => [77, 78],
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
