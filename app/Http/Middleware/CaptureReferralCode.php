<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CaptureReferralCode
{
    /**
     * Persist a ?ref= code in session so registration can attach referred_by.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ref = $request->query('ref');

        if (is_string($ref) && $ref !== '' && strlen($ref) <= 16) {
            $request->session()->put('referral_code', strtoupper($ref));
        }

        return $next($request);
    }
}
