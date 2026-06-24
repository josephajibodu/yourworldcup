<?php

namespace App\FootballData;

use GuzzleHttp\TransferStats;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class FootballDataApiClient
{
    public function __construct(
        private string $baseUrl,
        private string $token,
        private int $competitionId,
        private int $season,
        private int $timeout,
    ) {}

    public static function fromConfig(): self
    {
        $token = config('football_data.api_token');

        if (! is_string($token) || $token === '') {
            throw new RuntimeException('FOOTBALL_DATA_API_TOKEN is not configured.');
        }

        return new self(
            baseUrl: rtrim((string) config('football_data.api_base_url'), '/'),
            token: $token,
            competitionId: (int) config('football_data.competition_id'),
            season: (int) config('football_data.season'),
            timeout: (int) config('football_data.api_timeout', 15),
        );
    }

    /**
     * @param  array<string, scalar|null>  $query
     * @return list<array<string, mixed>>
     */
    public function competitionMatches(array $query = []): array
    {
        $query = array_merge(['season' => $this->season], $query);
        $path = "/v4/competitions/{$this->competitionId}/matches";

        try {
            $response = $this->client()->get($path, $query);
        } catch (ConnectionException $exception) {
            $this->logRequest('GET', $path, $query, null, $exception->getMessage());

            throw $exception;
        }

        $this->logRequest('GET', $path, $query, $response);

        $this->ensureSuccessful($response);

        $matches = $response->json('matches');

        return is_array($matches) ? $matches : [];
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->withHeaders(['X-Auth-Token' => $this->token])
            ->acceptJson()
            ->timeout($this->timeout);
    }

    private function ensureSuccessful(Response $response): void
    {
        if ($response->successful()) {
            return;
        }

        throw new RuntimeException(sprintf(
            'football-data.org request failed [%d]: %s',
            $response->status(),
            $response->body(),
        ));
    }

    /**
     * @param  array<string, scalar|null>  $requestBody
     */
    private function logRequest(
        string $method,
        string $path,
        array $requestBody,
        ?Response $response,
        ?string $error = null,
    ): void {
        Log::channel('outgoing_api')->info('Football Data API request', [
            'method' => $method,
            'url' => $this->baseUrl.$path,
            'request_body' => $requestBody,
            'status' => $response?->status(),
            'response_body' => $response !== null ? $this->responseBody($response) : null,
            'duration_ms' => $this->durationMs($response?->transferStats),
            'error' => $error,
        ]);
    }

    private function responseBody(Response $response): array|string|null
    {
        if ($response->body() === '') {
            return null;
        }

        $json = $response->json();

        return is_array($json) ? $json : $response->body();
    }

    private function durationMs(?TransferStats $stats): ?float
    {
        if ($stats === null) {
            return null;
        }

        return round($stats->getTransferTime() * 1000, 2);
    }
}
