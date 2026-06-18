<?php

namespace App\FootballData;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
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

        $response = $this->client()->get(
            "/v4/competitions/{$this->competitionId}/matches",
            $query,
        );

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
}
