<?php

namespace App\Predictions\Settlement;

class SettlerRegistry
{
    /** @var array<string, Settler> */
    protected array $settlers = [];

    public function register(string $key, Settler $settler): void
    {
        $this->settlers[$key] = $settler;
    }

    /**
     * Resolve the settler for a market, or null when the market has no
     * automatic settler (it must then be settled manually by an admin).
     */
    public function resolve(string $key): ?Settler
    {
        return $this->settlers[$key] ?? null;
    }

    public function has(string $key): bool
    {
        return isset($this->settlers[$key]);
    }
}
