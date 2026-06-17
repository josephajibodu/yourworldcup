<?php

namespace App\Http\Controllers;

use App\Models\Referral;
use App\Referrals\ReferralCodeGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class ReferralController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $rules = [
            'pointsPerReferral' => config('referrals.points_per_referral'),
            'dailyCap' => config('referrals.daily_cap'),
        ];

        if ($user === null) {
            return Inertia::render('referrals', [
                'rules' => $rules,
                'share' => null,
                'stats' => null,
            ]);
        }

        if ($user->referral_code === null) {
            $user->forceFill(['referral_code' => ReferralCodeGenerator::generate()])->save();
        }

        $watDate = Carbon::now(config('predictions.timezone'))->toDateString();

        return Inertia::render('referrals', [
            'rules' => $rules,
            'share' => [
                'code' => $user->referral_code,
                'url' => route('register', ['ref' => $user->referral_code], absolute: true),
            ],
            'stats' => [
                'totalReferrals' => Referral::query()->where('referrer_id', $user->id)->count(),
                'totalPoints' => (int) Referral::query()->where('referrer_id', $user->id)->sum('points'),
                'todayCount' => Referral::query()
                    ->where('referrer_id', $user->id)
                    ->where('wat_date', $watDate)
                    ->count(),
                'isVerified' => $user->isVerifiedForReferrals(),
                'hasMadePrediction' => $user->hasMadePrediction(),
            ],
        ]);
    }
}
