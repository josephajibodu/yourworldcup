<?php

use App\Predictions\Scoring\ExactScoreScorer;
use App\Predictions\Scoring\MatchWinnerScorer;

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
    | Day boundary for the daily leaderboard. All user-facing "days" are computed
    | in this timezone (West Africa Time) regardless of where kickoffs fall.
    */
    'timezone' => 'Africa/Lagos',
];
