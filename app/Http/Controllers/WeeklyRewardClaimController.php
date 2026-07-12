<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWeeklyRewardClaimRequest;
use App\Rewards\WeeklyRewardService;
use Illuminate\Http\RedirectResponse;

class WeeklyRewardClaimController extends Controller
{
    public function store(
        StoreWeeklyRewardClaimRequest $request,
        WeeklyRewardService $rewards,
    ): RedirectResponse {
        $rewards->submitClaim($request->user(), $request->validated());

        return back()->with('rewardClaimSubmitted', true);
    }
}
