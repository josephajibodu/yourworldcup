<?php

use App\FootballData\FootballDataApiClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    $this->logPath = storage_path('logs/outgoing-api-test.log');

    config([
        'football_data.api_token' => 'test-token',
        'football_data.api_base_url' => 'https://api.football-data.org',
        'football_data.competition_id' => 2000,
        'football_data.season' => 2026,
        'logging.channels.outgoing_api' => [
            'driver' => 'single',
            'path' => $this->logPath,
            'level' => 'debug',
            'replace_placeholders' => true,
        ],
    ]);

    Log::forgetChannel('outgoing_api');

    if (File::exists($this->logPath)) {
        File::delete($this->logPath);
    }
});

afterEach(function () {
    if (File::exists($this->logPath)) {
        File::delete($this->logPath);
    }
});

it('logs football data api requests with full request and response bodies', function () {
    Http::fake([
        'api.football-data.org/v4/competitions/2000/matches*' => Http::response([
            'matches' => [
                ['id' => 537327, 'status' => 'FINISHED'],
            ],
        ], 200),
    ]);

    $matches = FootballDataApiClient::fromConfig()->competitionMatches([
        'dateFrom' => '2026-06-11',
        'dateTo' => '2026-06-12',
    ]);

    expect($matches)->toHaveCount(1)
        ->and(File::exists($this->logPath))->toBeTrue();

    $log = File::get($this->logPath);

    expect($log)
        ->toContain('Football Data API request')
        ->toContain('"method":"GET"')
        ->toContain('https://api.football-data.org/v4/competitions/2000/matches')
        ->toContain('"dateFrom":"2026-06-11"')
        ->toContain('"dateTo":"2026-06-12"')
        ->toContain('"season":2026')
        ->toContain('"status":200')
        ->toContain('"id":537327')
        ->not->toContain('headers')
        ->not->toContain('test-token');
});

it('logs failed football data api connections', function () {
    Http::fake(fn () => Http::failedConnection('Connection timed out'));

    try {
        FootballDataApiClient::fromConfig()->competitionMatches();
    } catch (ConnectionException) {
        //
    }

    expect(File::exists($this->logPath))->toBeTrue();

    $log = File::get($this->logPath);

    expect($log)
        ->toContain('Football Data API request')
        ->toContain('Connection timed out')
        ->toContain('"status":null')
        ->toContain('"response_body":null');
});

it('does not log unrelated outgoing http client requests', function () {
    Http::fake([
        'https://api.example.com/*' => Http::response(['ok' => true], 200),
    ]);

    Http::get('https://api.example.com/v1/items');

    expect(File::exists($this->logPath))->toBeFalse();
});
