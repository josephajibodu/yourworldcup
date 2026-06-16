<?php

namespace App\Support;

use App\Models\User;

class AuthRedirect
{
    public static function path(?User $user): string
    {
        if ($user?->isSiteAdmin()) {
            return route('dashboard', absolute: false);
        }

        return route('predict', absolute: false);
    }
}
