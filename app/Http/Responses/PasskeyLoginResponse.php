<?php

namespace App\Http\Responses;

use App\Support\AuthRedirect;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Passkeys\Contracts\PasskeyLoginResponse as PasskeyLoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

class PasskeyLoginResponse implements PasskeyLoginResponseContract
{
    /**
     * @param  Request  $request
     */
    public function toResponse($request): Response
    {
        $redirect = AuthRedirect::path($request->user());

        if ($request->wantsJson()) {
            return new JsonResponse([
                'redirect' => redirect()->intended($redirect)->getTargetUrl(),
            ], 200);
        }

        return redirect()->intended($redirect);
    }
}
