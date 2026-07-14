<?php

use App\Predictions\Scoring\BooleanAnswerScorer;
use App\Predictions\Scoring\ExactScoreScorer;
use App\Predictions\Scoring\MatchWinnerScorer;
use App\Predictions\Scoring\SelectedOptionScorer;
use App\Predictions\Settlement\AwayCleanSheetSettler;
use App\Predictions\Settlement\ExactScoreSettler;
use App\Predictions\Settlement\HighestBookingSettler;
use App\Predictions\Settlement\HomeCleanSheetSettler;
use App\Predictions\Settlement\LastGoalSettler;
use App\Predictions\Settlement\MatchWinnerSettler;
use App\Predictions\Settlement\PenaltyShootoutSettler;
use App\Predictions\Settlement\ToQualifySettler;

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
        'to_qualify' => SelectedOptionScorer::class,
        'penalty_shootout' => BooleanAnswerScorer::class,
        'home_clean_sheet' => BooleanAnswerScorer::class,
        'away_clean_sheet' => BooleanAnswerScorer::class,
        'last_goal' => SelectedOptionScorer::class,
        'highest_booking' => SelectedOptionScorer::class,
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
        'to_qualify' => ToQualifySettler::class,
        'penalty_shootout' => PenaltyShootoutSettler::class,
        'home_clean_sheet' => HomeCleanSheetSettler::class,
        'away_clean_sheet' => AwayCleanSheetSettler::class,
        'last_goal' => LastGoalSettler::class,
        'highest_booking' => HighestBookingSettler::class,
    ],

    /*
    | Day boundary for the daily leaderboard. All user-facing "days" are computed
    | in this timezone (West Africa Time) regardless of where kickoffs fall.
    */
    'timezone' => 'Africa/Lagos',

    /*
    | WAT calendar bounds for the World Cup tournament (daily admin views).
    */
    'tournament_start' => '2026-06-11',
    'tournament_end' => '2026-07-19',

    /*
    | How many calendar days ahead of today (in the timezone above) users may
    | browse and submit predictions. 1 means today and tomorrow only.
    */
    'visible_days_ahead' => 1,

    /*
    | Expected maximum match length (regular time + stoppage + half-time) used
    | to infer live status when status is still "scheduled".
    */
    'match_duration_minutes' => 120,
];
