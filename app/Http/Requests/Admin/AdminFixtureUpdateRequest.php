<?php

namespace App\Http\Requests\Admin;

use App\Enums\FixtureStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminFixtureUpdateRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
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
            'settle' => ['boolean'],
        ];
    }

    public function shouldSettle(): bool
    {
        return $this->boolean('settle');
    }
}
