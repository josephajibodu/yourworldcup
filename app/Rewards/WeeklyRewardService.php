<?php

namespace App\Rewards;

use App\Enums\FixtureStatus;
use App\Enums\RewardPreference;
use App\Models\Fixture;
use App\Models\User;
use App\Models\WeeklyRewardClaim;
use App\Predictions\Leaderboard\LeaderboardService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WeeklyRewardService
{
    public function __construct(private LeaderboardService $leaderboard) {}

    /**
     * Whether the week's Sunday fixtures have finished — the last Sunday
     * match must be final before reward claims open.
     */
    public function isWeekReadyForClaims(string $weekStart): bool
    {
        $lastSundayMatch = $this->lastSundayFixture($weekStart);

        return $lastSundayMatch !== null
            && $lastSundayMatch->status === FixtureStatus::Final;
    }

    /**
     * @return array{
     *     ready: bool,
     *     eligible: array<int, array{
     *         rank: int,
     *         passedDown: bool,
     *         passedFromName: string|null,
     *         passOnMessage: string|null
     *     }>,
     *     submitted: array<int, array{passedOn: bool, preference: string|null, passOnMessage: string|null}>
     * }
     */
    public function statusForUser(?User $user, string $weekStart): array
    {
        if ($user === null) {
            return [
                'ready' => $this->isWeekReadyForClaims($weekStart),
                'eligible' => [],
                'submitted' => [],
            ];
        }

        $existingClaim = WeeklyRewardClaim::query()
            ->where('week_start', $weekStart)
            ->where('user_id', $user->id)
            ->first();

        $submitted = $existingClaim === null ? [] : [[
            'passedOn' => $existingClaim->passed_on,
            'preference' => $existingClaim->preference?->value,
            'passOnMessage' => $existingClaim->pass_on_message,
        ]];

        if (! $this->isWeekReadyForClaims($weekStart)) {
            return [
                'ready' => false,
                'eligible' => [],
                'submitted' => $submitted,
            ];
        }

        $eligible = [];

        if ($existingClaim === null && $this->isUserEligible($user, $weekStart)) {
            $rank = $this->userWeeklyRank($user, $weekStart);

            $eligible[] = [
                'rank' => $rank,
                ...$this->passDownContext($weekStart, $rank),
            ];
        }

        return [
            'ready' => true,
            'eligible' => $eligible,
            'submitted' => $submitted,
        ];
    }

    /**
     * @param  array{week_start: string, passed_on: bool, preference?: string|null, pass_on_message?: string|null}  $data
     */
    public function submitClaim(User $user, array $data): WeeklyRewardClaim
    {
        $weekStart = $data['week_start'];
        $passedOn = filter_var($data['passed_on'], FILTER_VALIDATE_BOOLEAN);

        if (! $this->isWeekReadyForClaims($weekStart)) {
            throw ValidationException::withMessages([
                'week_start' => 'Weekly rewards are not open for this week yet.',
            ]);
        }

        if (WeeklyRewardClaim::query()
            ->where('week_start', $weekStart)
            ->where('user_id', $user->id)
            ->exists()) {
            throw ValidationException::withMessages([
                'week_start' => 'You have already responded for this week.',
            ]);
        }

        if (! $this->isUserEligible($user, $weekStart)) {
            throw ValidationException::withMessages([
                'week_start' => 'You are not currently eligible for this week\'s airtime.',
            ]);
        }

        $rank = $this->userWeeklyRank($user, $weekStart);
        $preference = null;

        if (! $passedOn) {
            $preference = RewardPreference::from($data['preference'] ?? '');
        }

        $payoutDetails = $passedOn ? [] : $this->payoutDetailsFromClaimData($data, $preference);

        return DB::transaction(function () use ($user, $weekStart, $rank, $passedOn, $preference, $data, $payoutDetails): WeeklyRewardClaim {
            if (WeeklyRewardClaim::query()
                ->where('week_start', $weekStart)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->exists()) {
                throw ValidationException::withMessages([
                    'week_start' => 'You have already responded for this week.',
                ]);
            }

            if (! $passedOn && $this->rewardsRemaining($weekStart) === 0) {
                throw ValidationException::withMessages([
                    'week_start' => 'All weekly rewards have already been claimed.',
                ]);
            }

            return WeeklyRewardClaim::create([
                'week_start' => $weekStart,
                'user_id' => $user->id,
                'reward_position' => null,
                'leaderboard_rank' => $rank,
                'preference' => $preference,
                'passed_on' => $passedOn,
                'pass_on_message' => $passedOn ? ($data['pass_on_message'] ?? null) : null,
                ...$payoutDetails,
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, string|null>
     */
    private function payoutDetailsFromClaimData(array $data, RewardPreference $preference): array
    {
        if (in_array($preference, [RewardPreference::Airtime, RewardPreference::Data], true)) {
            return [
                'phone_number' => $data['phone_number'] ?? null,
                'mobile_network' => $data['mobile_network'] ?? null,
                'account_holder_name' => null,
                'bank_name' => null,
                'account_number' => null,
            ];
        }

        return [
            'phone_number' => null,
            'mobile_network' => null,
            'account_holder_name' => $data['account_holder_name'] ?? null,
            'bank_name' => $data['bank_name'] ?? null,
            'account_number' => $data['account_number'] ?? null,
        ];
    }

    public function isUserEligible(User $user, string $weekStart): bool
    {
        $rank = $this->userWeeklyRank($user, $weekStart);

        if ($rank === null || $rank > $this->considerationCeiling($weekStart)) {
            return false;
        }

        if ($this->rewardsRemaining($weekStart) === 0) {
            return false;
        }

        return ! WeeklyRewardClaim::query()
            ->where('week_start', $weekStart)
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * Highest rank currently in the running: top N, plus one rank for each pass.
     */
    public function considerationCeiling(string $weekStart): int
    {
        return $this->weeklyPositions() + $this->passCount($weekStart);
    }

    public function lastSundayFixture(string $weekStart): ?Fixture
    {
        $weekSunday = Carbon::parse($weekStart, config('predictions.timezone'))
            ->addDays(6)
            ->toDateString();

        [$start, $end] = $this->watWeekWindow($weekStart);

        return Fixture::query()
            ->onWatDate($weekSunday)
            ->whereBetween('kickoff_at', [$start, $end])
            ->orderByDesc('kickoff_at')
            ->first();
    }

    private function userWeeklyRank(User $user, string $weekStart): ?int
    {
        $row = $this->leaderboard
            ->weekly($weekStart, 50)
            ->firstWhere('userId', $user->id);

        return $row !== null ? (int) $row['rank'] : null;
    }

    private function weeklyPositions(): int
    {
        return (int) config('rewards.weekly_positions', 3);
    }

    private function passCount(string $weekStart): int
    {
        return WeeklyRewardClaim::query()
            ->where('week_start', $weekStart)
            ->where('passed_on', true)
            ->count();
    }

    private function claimedCount(string $weekStart): int
    {
        return WeeklyRewardClaim::query()
            ->where('week_start', $weekStart)
            ->where('passed_on', false)
            ->count();
    }

    private function rewardsRemaining(string $weekStart): int
    {
        return max(0, $this->weeklyPositions() - $this->claimedCount($weekStart));
    }

    /**
     * @return array{passedDown: bool, passedFromName: string|null, passOnMessage: string|null}
     */
    private function passDownContext(string $weekStart, int $leaderboardRank): array
    {
        if ($leaderboardRank <= $this->weeklyPositions()) {
            return [
                'passedDown' => false,
                'passedFromName' => null,
                'passOnMessage' => null,
            ];
        }

        $lastPass = WeeklyRewardClaim::query()
            ->where('week_start', $weekStart)
            ->where('passed_on', true)
            ->with('user')
            ->latest()
            ->first();

        return [
            'passedDown' => true,
            'passedFromName' => $lastPass?->user?->name,
            'passOnMessage' => $lastPass?->pass_on_message,
        ];
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function watWeekWindow(string $weekStart): array
    {
        $start = Carbon::parse($weekStart, config('predictions.timezone'))->startOfDay();

        return [$start->copy()->utc(), $start->copy()->addWeek()->utc()];
    }
}
