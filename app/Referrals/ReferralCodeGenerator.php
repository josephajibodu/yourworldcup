<?php

namespace App\Referrals;

use App\Models\User;
use Illuminate\Support\Str;

class ReferralCodeGenerator
{
    public static function generate(): string
    {
        $length = config('referrals.code_length', 8);

        do {
            $code = Str::upper(Str::random($length));
        } while (User::query()->where('referral_code', $code)->exists());

        return $code;
    }
}
