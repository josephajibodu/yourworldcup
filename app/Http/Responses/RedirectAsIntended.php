<?php

namespace App\Http\Responses;

use App\Support\AuthRedirect;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RedirectAsIntended implements Responsable
{
    public function __construct(public string $name) {}

    /**
     * @param  Request  $request
     */
    public function toResponse($request): RedirectResponse
    {
        return redirect()->intended(AuthRedirect::path($request->user()));
    }
}
