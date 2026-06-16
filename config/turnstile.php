<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Turnstile Enabled
    |--------------------------------------------------------------------------
    |
    | When disabled, Turnstile validation is skipped entirely and the widget
    | is not rendered on the frontend.
    |
    */

    'enabled' => env('TURNSTILE_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Turnstile Keys
    |--------------------------------------------------------------------------
    */

    'site_key' => env('TURNSTILE_SITE_KEY'),

    'secret_key' => env('TURNSTILE_SECRET_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Protected Routes
    |--------------------------------------------------------------------------
    |
    | POST routes that require a valid Turnstile token when enabled.
    |
    */

    'routes' => [
        'login.store',
        'register.store',
        'password.email',
        'password.update',
    ],

];
