<?php

namespace App\Rules;

use App\Services\TurnstileVerifier;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidTurnstile implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! config('turnstile.enabled')) {
            return;
        }

        $verifier = app(TurnstileVerifier::class);

        if (! $verifier->verify(is_string($value) ? $value : null, request()->ip())) {
            $fail('The CAPTCHA verification failed. Please try again.');
        }
    }
}
