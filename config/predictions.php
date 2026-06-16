<?php

use App\Predictions\Scoring\ExactScoreScorer;
use App\Predictions\Scoring\MatchWinnerScorer;
use App\Predictions\Settlement\ExactScoreSettler;
use App\Predictions\Settlement\MatchWinnerSettler;

return [
    /*
    |--------------------------------------------------------------------------
    | Market scorers
    |--------------------------------------------------------------------------
    |
    | Maps a prediction_markets.scorer key to the class that scores it. Adding a
    | new market type means adding one entry here and one Scorer class — nothing
    | in leaderboards, jobs, or realtime changes. Classes are resolved from the
    | container, so scorers may depend on other services.
    |
    */
    'scorers' => [
        'match_winner' => MatchWinnerScorer::class,
        'exact_score' => ExactScoreScorer::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Market settlers
    |--------------------------------------------------------------------------
    |
    | Maps a prediction_markets.scorer key to the class that derives that
    | market's settlement_value from a finished fixture (the scorer/settler
    | pair share a key). A market with no settler here is settled manually by
    | an admin. Adding an auto-settleable market means one settler class and
    | one entry — the scoring engine is untouched.
    |
    */
    'settlers' => [
        'match_winner' => MatchWinnerSettler::class,
        'exact_score' => ExactScoreSettler::class,
    ],

    /*
    | Day boundary for the daily leaderboard. All user-facing "days" are computed
    | in this timezone (West Africa Time) regardless of where kickoffs fall.
    */
    'timezone' => 'Africa/Lagos',
];
