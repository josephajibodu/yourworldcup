<?php

namespace App\Predictions\Submission;

use App\Enums\MarketInputType;
use App\Models\FixtureMarket;
use Illuminate\Validation\ValidationException;

class MarketValueValidator
{
    /**
     * Validate a raw submitted value against the market's input type and return
     * the normalised value to persist. Throws a keyed ValidationException so the
     * error surfaces on the offending fixture market in the UI.
     *
     * @param  array<string, mixed>  $raw
     * @return array<string, mixed>
     */
    public function validate(FixtureMarket $fixtureMarket, array $raw): array
    {
        return match ($fixtureMarket->market->input_type) {
            MarketInputType::SingleSelect => $this->singleSelect($fixtureMarket, $raw),
            MarketInputType::Boolean => $this->boolean($fixtureMarket, $raw),
            MarketInputType::Scoreline => $this->scoreline($fixtureMarket, $raw),
            MarketInputType::Integer => $this->integer($fixtureMarket, $raw),
        };
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return array<string, string>
     */
    private function singleSelect(FixtureMarket $fixtureMarket, array $raw): array
    {
        $selected = $raw['selected'] ?? null;

        $allowed = array_map(
            static fn (array $option): mixed => $option['value'],
            $fixtureMarket->resolvedOptions() ?? [],
        );

        if (! is_string($selected) || ! in_array($selected, $allowed, true)) {
            $this->fail($fixtureMarket, 'Choose one of the available options.');
        }

        return ['selected' => $selected];
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return array<string, bool>
     */
    private function boolean(FixtureMarket $fixtureMarket, array $raw): array
    {
        $answer = $raw['answer'] ?? null;

        if (! is_bool($answer)) {
            $this->fail($fixtureMarket, 'Answer yes or no.');
        }

        return ['answer' => $answer];
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return array<string, int>
     */
    private function scoreline(FixtureMarket $fixtureMarket, array $raw): array
    {
        $home = $raw['home'] ?? null;
        $away = $raw['away'] ?? null;

        if (! $this->isScore($home) || ! $this->isScore($away)) {
            $this->fail($fixtureMarket, 'Enter a valid scoreline (0–30 each side).');
        }

        return ['home' => (int) $home, 'away' => (int) $away];
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return array<string, int>
     */
    private function integer(FixtureMarket $fixtureMarket, array $raw): array
    {
        $value = $raw['value'] ?? null;

        if (! is_numeric($value) || (int) $value < 0) {
            $this->fail($fixtureMarket, 'Enter a valid number.');
        }

        return ['value' => (int) $value];
    }

    private function isScore(mixed $value): bool
    {
        return is_numeric($value) && (int) $value >= 0 && (int) $value <= 30;
    }

    /**
     * @return never
     */
    private function fail(FixtureMarket $fixtureMarket, string $message): void
    {
        throw ValidationException::withMessages([
            "markets.{$fixtureMarket->id}" => $message,
        ]);
    }
}
