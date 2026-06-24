<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class TwitterHandle
{
    /**
     * @return array<int, ValidationRule|string>
     */
    public static function rules(?int $userId = null): array
    {
        return [
            'required',
            'string',
            'max:15',
            'regex:/^[A-Za-z0-9_]+$/',
            $userId === null
                ? Rule::unique(User::class, 'name')
                : Rule::unique(User::class, 'name')->ignore($userId),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'name.required' => 'Enter your X handle.',
            'name.max' => 'X handles can be at most 15 characters.',
            'name.regex' => 'Use letters, numbers, and underscores only.',
            'name.unique' => 'That X handle is already taken.',
        ];
    }

    public static function normalize(string $value): string
    {
        return ltrim(trim($value), '@');
    }
}
