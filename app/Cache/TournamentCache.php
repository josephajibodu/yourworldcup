<?php

namespace App\Cache;

use Closure;
use DateInterval;
use DateTimeInterface;
use Illuminate\Support\Facades\Cache;

class TournamentCache
{
    /** @var list<string> */
    public const array DOMAINS = ['bracket', 'predict', 'leaderboard'];

    /** @var array<string, int> */
    private array $versions = [];

    public function remember(
        string $domain,
        string $key,
        Closure $callback,
        DateTimeInterface|DateInterval|int|null $ttl = 86400,
    ): mixed {
        return Cache::remember($this->key($domain, $key), $ttl, $callback);
    }

    public function bump(string $domain): void
    {
        $key = $this->versionKey($domain);

        $this->versions[$domain] = (int) Cache::get($key, 0) + 1;
        Cache::forever($key, $this->versions[$domain]);
    }

    public function version(string $domain): int
    {
        return $this->versions[$domain] ??= (int) Cache::get($this->versionKey($domain), 0);
    }

    private function versionKey(string $domain): string
    {
        return "tournament:{$domain}:version";
    }

    private function key(string $domain, string $key): string
    {
        return "tournament:{$domain}:v{$this->version($domain)}:{$key}";
    }
}
