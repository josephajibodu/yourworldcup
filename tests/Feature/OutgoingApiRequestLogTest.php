<?php

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    $this->logPath = storage_path('logs/outgoing-api-test.log');

    config([
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

it('logs outgoing http client requests to the outgoing_api channel', function () {
    Http::fake([
        'https://api.example.com/*' => Http::response(['ok' => true], 200),
    ]);

    Http::withHeaders(['X-Auth-Token' => 'secret-token'])
        ->get('https://api.example.com/v1/items', ['page' => 1]);

    expect(File::exists($this->logPath))->toBeTrue();

    $log = File::get($this->logPath);

    expect($log)
        ->toContain('Outgoing HTTP request')
        ->toContain('"method":"GET"')
        ->toContain('https://api.example.com/v1/items?page=1')
        ->toContain('"status":200')
        ->toContain('[redacted]')
        ->not->toContain('secret-token');
});

it('logs failed outgoing http client connections to the outgoing_api channel', function () {
    Http::fake(fn () => Http::failedConnection('Connection timed out'));

    try {
        Http::get('https://api.example.com/v1/items');
    } catch (ConnectionException) {
        //
    }

    expect(File::exists($this->logPath))->toBeTrue();

    $log = File::get($this->logPath);

    expect($log)
        ->toContain('Outgoing HTTP request')
        ->toContain('Connection timed out')
        ->toContain('"status":null');
});
