<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;

class PlayerCountController extends Controller
{
    /**
     * Live player count that gates the grand-prize unlock.
     *
     * For now this is the registered-user count; swap to verified-phone players
     * once phone-OTP onboarding lands — the frontend reads it through one hook.
     */
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'count' => User::query()->count(),
        ]);
    }
}
