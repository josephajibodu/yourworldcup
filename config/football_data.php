<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Data provider key
    |--------------------------------------------------------------------------
    |
    | Stored on teams.provider and fixtures.provider when linked from the
    | football-data.org export files below.
    |
    */
    'provider' => 'football-data',

    /*
    |--------------------------------------------------------------------------
    | Local JSON exports
    |--------------------------------------------------------------------------
    |
    | Snapshot API responses here to link ids without calling the network on
    | every import. Refresh these files when the upstream schedule changes.
    |
    */
    'data_path' => database_path('data/football_data_org'),

    'teams_file' => 'teams.json',

    'matches_file' => 'matches.json',

    /*
    |--------------------------------------------------------------------------
    | Team code aliases
    |--------------------------------------------------------------------------
    |
    | Maps football-data.org TLA values to the fifa_code stored locally when
    | they differ.
    |
    */
    'team_code_aliases' => [
        'URY' => 'URU',
    ],
];
