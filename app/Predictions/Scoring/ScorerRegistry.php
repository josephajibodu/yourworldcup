<?php

namespace App\Predictions\Scoring;

use InvalidArgumentException;

class ScorerRegistry
{
    /** @var array<string, Scorer> */
    protected array $scorers = [];

    public function register(string $key, Scorer $scorer): void
    {
        $this->scorers[$key] = $scorer;
    }

    public function resolve(string $key): Scorer
    {
        return $this->scorers[$key]
            ?? throw new InvalidArgumentException("No scorer registered for market key [{$key}].");
    }

    public function has(string $key): bool
    {
        return isset($this->scorers[$key]);
    }
}
