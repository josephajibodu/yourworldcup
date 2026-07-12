<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RewardPreference;
use App\Http\Controllers\Controller;
use App\Models\WeeklyRewardClaim;
use App\Predictions\Leaderboard\LeaderboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WeeklyRewardClaimController extends Controller
{
    public function __construct(private LeaderboardService $leaderboard) {}

    public function index(Request $request): Response
    {
        $weeks = $this->leaderboard->weeklyDates();
        $selectedWeek = $this->selectedWeek($request->query('week'), $weeks);

        $claimsQuery = WeeklyRewardClaim::query()
            ->with('user:id,name,email')
            ->latest();

        if ($selectedWeek !== null) {
            $claimsQuery->where('week_start', $selectedWeek);
        }

        $claims = $claimsQuery
            ->paginate(50)
            ->withQueryString()
            ->through(fn (WeeklyRewardClaim $claim): array => $this->formatClaim($claim));

        return Inertia::render('admin/reward-claims/index', [
            'claims' => $claims,
            'dates' => $weeks,
            'selectedWeek' => $selectedWeek,
            'summary' => $this->summary($selectedWeek),
        ]);
    }

    /**
     * @param  array<int, string>  $weeks
     */
    private function selectedWeek(?string $requested, array $weeks): ?string
    {
        if ($requested !== null && in_array($requested, $weeks, true)) {
            return $requested;
        }

        return $weeks === [] ? null : end($weeks);
    }

    /**
     * @return array{
     *     total: int,
     *     passed: int,
     *     claimed: int,
     *     airtime: int,
     *     data: int,
     *     cash: int
     * }
     */
    private function summary(?string $weekStart): array
    {
        $query = WeeklyRewardClaim::query();

        if ($weekStart !== null) {
            $query->where('week_start', $weekStart);
        }

        $claims = $query->get();

        return [
            'total' => $claims->count(),
            'passed' => $claims->where('passed_on', true)->count(),
            'claimed' => $claims->where('passed_on', false)->count(),
            'airtime' => $claims->where('preference', RewardPreference::Airtime)->count(),
            'data' => $claims->where('preference', RewardPreference::Data)->count(),
            'cash' => $claims->where('preference', RewardPreference::Cash)->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatClaim(WeeklyRewardClaim $claim): array
    {
        return [
            'id' => $claim->id,
            'weekStart' => $claim->week_start->toDateString(),
            'leaderboardRank' => $claim->leaderboard_rank,
            'passedOn' => $claim->passed_on,
            'preference' => $claim->preference?->value,
            'preferenceLabel' => match ($claim->preference) {
                RewardPreference::Airtime => 'Airtime',
                RewardPreference::Data => 'Data',
                RewardPreference::Cash => 'Cash',
                default => null,
            },
            'phoneNumber' => $claim->phone_number,
            'mobileNetwork' => $claim->mobile_network?->value,
            'mobileNetworkLabel' => $claim->mobile_network?->label(),
            'accountHolderName' => $claim->account_holder_name,
            'bankName' => $claim->bank_name,
            'accountNumber' => $claim->account_number,
            'passOnMessage' => $claim->pass_on_message,
            'submittedAt' => $claim->created_at?->toIso8601String(),
            'user' => [
                'id' => $claim->user->id,
                'name' => $claim->user->name,
                'email' => $claim->user->email,
            ],
        ];
    }
}
