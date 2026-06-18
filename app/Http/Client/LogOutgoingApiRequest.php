<?php

namespace App\Http\Client;

use GuzzleHttp\TransferStats;
use Illuminate\Http\Client\Events\ConnectionFailed;
use Illuminate\Http\Client\Events\ResponseReceived;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Log;

class LogOutgoingApiRequest
{
    /** @var list<string> */
    private const REDACTED_HEADERS = [
        'authorization',
        'x-auth-token',
        'cookie',
        'set-cookie',
    ];

    /** @var list<string> */
    private const REDACTED_BODY_KEYS = [
        'secret',
        'token',
        'password',
        'response',
    ];

    public function handleResponseReceived(ResponseReceived $event): void
    {
        $this->log(
            request: $event->request,
            status: $event->response->status(),
            durationMs: $this->durationMs($event->response->transferStats),
        );
    }

    public function handleConnectionFailed(ConnectionFailed $event): void
    {
        $this->log(
            request: $event->request,
            error: $event->exception->getMessage(),
        );
    }

    private function log(Request $request, ?int $status = null, ?float $durationMs = null, ?string $error = null): void
    {
        Log::channel('outgoing_api')->info('Outgoing HTTP request', [
            'method' => $request->method(),
            'url' => $request->url(),
            'query' => $this->query($request->url()),
            'headers' => $this->redactHeaders($request->headers()),
            'body' => $this->redactBody($request->data()),
            'status' => $status,
            'duration_ms' => $durationMs !== null ? round($durationMs, 2) : null,
            'error' => $error,
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function query(string $url): ?array
    {
        $queryString = parse_url($url, PHP_URL_QUERY);

        if (! is_string($queryString) || $queryString === '') {
            return null;
        }

        parse_str($queryString, $query);

        return is_array($query) ? $this->redactBody($query) : null;
    }

    /**
     * @param  array<string, list<string>>  $headers
     * @return array<string, list<string>>
     */
    private function redactHeaders(array $headers): array
    {
        $redacted = [];

        foreach ($headers as $name => $values) {
            $redacted[$name] = in_array(strtolower($name), self::REDACTED_HEADERS, true)
                ? ['[redacted]']
                : $values;
        }

        return $redacted;
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>|null
     */
    private function redactBody(array $body): ?array
    {
        if ($body === []) {
            return null;
        }

        $redacted = [];

        foreach ($body as $key => $value) {
            $redacted[$key] = is_string($key) && in_array(strtolower($key), self::REDACTED_BODY_KEYS, true)
                ? '[redacted]'
                : $value;
        }

        return $redacted;
    }

    private function durationMs(?TransferStats $stats): ?float
    {
        if ($stats === null) {
            return null;
        }

        return $stats->getTransferTime() * 1000;
    }
}
