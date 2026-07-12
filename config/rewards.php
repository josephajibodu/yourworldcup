<?php

return [
    /*
    | Number of weekly airtime rewards and the initial top ranks considered.
    | Each pass extends consideration by one rank in leaderboard order
    | (e.g. with 3: ranks 1–3 start; if 1st and 3rd pass → 4th and 5th join).
    */
    'weekly_positions' => (int) env('REWARDS_WEEKLY_POSITIONS', 3),
];
