<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TurnstileVerifier
{
    private const string VerifyUrl = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public function verify(?string $token, ?string $remoteIp = null): bool
    {
        if ($token === null || $token === '') {
            return false;
        }

        $secretKey = config('turnstile.secret_key');

        if ($secretKey === null || $secretKey === '') {
            return false;
        }

        $payload = [
            'secret' => $secretKey,
            'response' => $token,
        ];

        if ($remoteIp !== null) {
            $payload['remoteip'] = $remoteIp;
        }

        $response = Http::asForm()
            ->timeout(5)
            ->post(self::VerifyUrl, $payload);

        if (! $response->successful()) {
            return false;
        }

        return $response->json('success') === true;
    }
}
