<?php

namespace App\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ImpersonationManager
{
    public const SESSION_KEY = 'impersonator_id';

    public function isImpersonating(): bool
    {
        return session()->has(self::SESSION_KEY);
    }

    public function start(User $admin, User $target): void
    {
        session()->put(self::SESSION_KEY, $admin->id);

        Auth::login($target);
    }

    public function stop(): User
    {
        $adminId = session()->pull(self::SESSION_KEY);

        abort_if($adminId === null, 403);

        $admin = User::query()->findOrFail($adminId);

        Auth::login($admin);

        return $admin;
    }
}
