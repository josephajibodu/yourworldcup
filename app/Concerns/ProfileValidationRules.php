<?php

namespace App\Concerns;

use App\Models\User;
use App\Support\TwitterHandle;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait ProfileValidationRules
{
    /**
     * Get the validation rules used to validate user profiles.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function profileRules(?int $userId = null): array
    {
        return [
            'name' => $this->nameRules($userId),
            'email' => $this->emailRules($userId),
        ];
    }

    /**
     * Get the validation rules used to validate user X handles.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function nameRules(?int $userId = null): array
    {
        return TwitterHandle::rules($userId);
    }

    /**
     * Get the validation rules used to validate user emails.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function emailRules(?int $userId = null): array
    {
        return [
            'required',
            'string',
            'email',
            'max:255',
            $userId === null
                ? Rule::unique(User::class)
                : Rule::unique(User::class)->ignore($userId),
        ];
    }
}
