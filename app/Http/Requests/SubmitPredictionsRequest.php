<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitPredictionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'date' => ['required', 'date_format:Y-m-d'],
            'predictions' => ['present', 'array'],
            'predictions.*.fixture_market_id' => ['required', 'integer', 'exists:fixture_markets,id'],
            'predictions.*.value' => ['required', 'array'],
            'banker_fixture_market_id' => ['nullable', 'integer', 'exists:fixture_markets,id'],
        ];
    }

    /**
     * The picks, shaped for PredictionService::submitDay().
     *
     * @return array<int, array{fixture_market_id: int, value: array<string, mixed>}>
     */
    public function entries(): array
    {
        return array_map(
            static fn (array $entry): array => [
                'fixture_market_id' => (int) $entry['fixture_market_id'],
                'value' => $entry['value'],
            ],
            $this->validated('predictions'),
        );
    }

    public function bankerFixtureMarketId(): ?int
    {
        $value = $this->validated('banker_fixture_market_id');

        return $value === null ? null : (int) $value;
    }
}
