<?php

namespace App\Http\Middleware;

use App\Services\TurnstileVerifier;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class ValidateTurnstile
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('turnstile.enabled') || ! $request->isMethod('POST')) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();

        if (! in_array($routeName, config('turnstile.routes', []), true)) {
            return $next($request);
        }

        $token = $request->input('cf-turnstile-response');
        $verifier = app(TurnstileVerifier::class);

        if (! $verifier->verify(is_string($token) ? $token : null, $request->ip())) {
            throw ValidationException::withMessages([
                'cf-turnstile-response' => 'The CAPTCHA verification failed. Please try again.',
            ]);
        }

        return $next($request);
    }
}
