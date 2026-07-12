<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AdminPassOnWeeklyRewardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSiteAdmin() ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'week_start' => ['required', 'date_format:Y-m-d'],
            'pass_on_message' => ['nullable', 'string', 'max:500'],
        ];
    }
}
