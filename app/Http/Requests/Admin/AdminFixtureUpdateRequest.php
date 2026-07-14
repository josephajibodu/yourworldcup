<?php

namespace App\Http\Requests\Admin;

use App\Enums\FixtureStatus;
use App\Enums\HighestBookingOutcome;
use App\Enums\LastGoalOutcome;
use App\Enums\ResultDuration;
use App\Fixtures\FixtureResult;
use App\Predictions\Markets\M101PlayerMarkets;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AdminFixtureUpdateRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'status' => ['required', Rule::enum(FixtureStatus::class)],
            'home_score' => [
                'nullable',
                'integer',
                'min:0',
                'max:30',
                Rule::requiredIf(fn (): bool => $this->input('status') === FixtureStatus::Final->value),
            ],
            'away_score' => [
                'nullable',
                'integer',
                'min:0',
                'max:30',
                Rule::requiredIf(fn (): bool => $this->input('status') === FixtureStatus::Final->value),
            ],
            'extra_time_home' => ['nullable', 'integer', 'min:0', 'max:30'],
            'extra_time_away' => ['nullable', 'integer', 'min:0', 'max:30'],
            'penalties_home' => ['nullable', 'integer', 'min:0', 'max:30'],
            'penalties_away' => ['nullable', 'integer', 'min:0', 'max:30'],
            'result_duration' => ['nullable', Rule::enum(ResultDuration::class)],
            'last_goal' => ['nullable', Rule::enum(LastGoalOutcome::class)],
            'highest_booking' => ['nullable', Rule::enum(HighestBookingOutcome::class)],
            'settle' => ['boolean'],
        ];

        foreach (M101PlayerMarkets::keys() as $key) {
            $rules["player_outcomes.{$key}"] = ['nullable', 'boolean'];
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->input('status') !== FixtureStatus::Final->value) {
                return;
            }

            $home = $this->input('home_score');
            $away = $this->input('away_score');
            $penHome = $this->input('penalties_home');
            $penAway = $this->input('penalties_away');

            if ($penHome !== null && $penAway !== null && $home !== $away) {
                $validator->errors()->add(
                    'penalties_home',
                    'Penalty shootout scores require a level regular-time score.',
                );
            }
        });
    }

    public function shouldSettle(): bool
    {
        return $this->boolean('settle');
    }

    public function toFixtureResult(): ?FixtureResult
    {
        if ($this->input('status') !== FixtureStatus::Final->value) {
            return null;
        }

        return new FixtureResult(
            homeScore: (int) $this->input('home_score'),
            awayScore: (int) $this->input('away_score'),
            extraTimeHome: $this->filled('extra_time_home') ? (int) $this->input('extra_time_home') : null,
            extraTimeAway: $this->filled('extra_time_away') ? (int) $this->input('extra_time_away') : null,
            penaltiesHome: $this->filled('penalties_home') ? (int) $this->input('penalties_home') : null,
            penaltiesAway: $this->filled('penalties_away') ? (int) $this->input('penalties_away') : null,
            resultDuration: $this->filled('result_duration')
                ? ResultDuration::from($this->input('result_duration'))
                : null,
            lastGoal: $this->filled('last_goal')
                ? LastGoalOutcome::from($this->input('last_goal'))
                : null,
            highestBooking: $this->filled('highest_booking')
                ? HighestBookingOutcome::from($this->input('highest_booking'))
                : null,
            playerOutcomes: $this->playerOutcomes(),
        );
    }

    /**
     * @return array<string, bool>|null
     */
    private function playerOutcomes(): ?array
    {
        $raw = $this->input('player_outcomes');

        if (! is_array($raw)) {
            return null;
        }

        $outcomes = [];

        foreach (M101PlayerMarkets::keys() as $key) {
            if (! array_key_exists($key, $raw) || $raw[$key] === '' || $raw[$key] === null) {
                continue;
            }

            $outcomes[$key] = filter_var($raw[$key], FILTER_VALIDATE_BOOLEAN);
        }

        return $outcomes === [] ? null : $outcomes;
    }
}
