<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Referral points
    |--------------------------------------------------------------------------
    */
    'points_per_referral' => 1,

    /*
    |--------------------------------------------------------------------------
    | Daily cap (WAT)
    |--------------------------------------------------------------------------
    |
    | Maximum referral credits a referrer can earn per WAT calendar day.
    |
    */
    'daily_cap' => 5,

    /*
    |--------------------------------------------------------------------------
    | Referral code length
    |--------------------------------------------------------------------------
    */
    'code_length' => 8,

    /*
    |--------------------------------------------------------------------------
    | Verification column
    |--------------------------------------------------------------------------
    |
    | Column checked to confirm a user is verified for referral purposes.
    | Switch to phone_verified_at when phone OTP ships.
    |
    */
    'verification_column' => 'email_verified_at',
];
