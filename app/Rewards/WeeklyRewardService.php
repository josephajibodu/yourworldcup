<?php

namespace App\Rewards;

use App\Enums\RewardPreference;
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
     * Whether the self-service claim window is open for this week.
     * Claims are available on the week's Sunday and the following Monday (WAT).
     */
    public function isWeekReadyForClaims(string $weekStart): bool
    {
        return $this->openClaimWeekStart() === $weekStart;
    }

    public function openClaimWeekStart(): ?string
    {
        return $this->claimWeekStart();
    }

    /**
     * Weekly ranks still waiting for a claim or pass-on response.
     *
     * @return list<int>
     */
    public function pendingConsiderationRanks(string $weekStart): array
    {
        if (! $this->isWeekReadyForClaims($weekStart)) {
            return [];
        }

        if ($this->rewardsRemaining($weekStart) === 0) {
            return [];
        }

        $ceiling = $this->considerationCeiling($weekStart);
        $claimedUserIds = WeeklyRewardClaim::query()
            ->where('week_start', $weekStart)
            ->pluck('user_id')
            ->all();

        $pending = [];

        foreach ($this->leaderboard->weekly($weekStart, $ceiling) as $row) {
            if ($row['rank'] > $ceiling) {
                continue;
            }

            if (! in_array($row['userId'], $claimedUserIds, true)) {
                $pending[] = (int) $row['rank'];
            }
        }

        return $pending;
    }

    /**
     * Players within the consideration pool who have not responded yet.
     *
     * @return list<array{
     *     userId: int,
     *     rank: int,
     *     name: string,
     *     email: string
     * }>
     */
    public function unansweredConsiderationUsers(string $weekStart): array
    {
        if ($this->rewardsRemaining($weekStart) === 0) {
            return [];
        }

        $ceiling = $this->considerationCeiling($weekStart);
        $claimedUserIds = WeeklyRewardClaim::query()
            ->where('week_start', $weekStart)
            ->pluck('user_id')
            ->all();

        $pendingRows = $this->leaderboard
            ->weekly($weekStart, $ceiling)
            ->filter(fn (array $row): bool => $row['rank'] <= $ceiling
                && ! in_array($row['userId'], $claimedUserIds, true));

        if ($pendingRows->isEmpty()) {
            return [];
        }

        $emails = User::query()
            ->whereIn('id', $pendingRows->pluck('userId'))
            ->pluck('email', 'id');

        return $pendingRows
            ->map(fn (array $row): array => [
                'userId' => $row['userId'],
                'rank' => $row['rank'],
                'name' => $row['name'],
                'email' => (string) $emails->get($row['userId'], ''),
            ])
            ->values()
            ->all();
    }

    public function adminPassOn(User $user, string $weekStart, ?string $passOnMessage = null): WeeklyRewardClaim
    {
        if (! $this->canPassOn($user, $weekStart)) {
            throw ValidationException::withMessages([
                'week_start' => 'This player cannot be passed on for the selected week.',
            ]);
        }

        return $this->submitClaim($user, [
            'week_start' => $weekStart,
            'passed_on' => true,
            'pass_on_message' => $passOnMessage,
        ], allowOutsideClaimWindow: true);
    }

    /**
     * @return array{
     *     ready: bool,
     *     considerationCeiling: int,
     *     pendingRanks: list<int>,
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
        $ready = $this->isWeekReadyForClaims($weekStart);

        if ($user === null) {
            return [
                'ready' => $ready,
                'considerationCeiling' => $ready ? $this->considerationCeiling($weekStart) : 0,
                'pendingRanks' => $this->pendingConsiderationRanks($weekStart),
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

        if (! $ready) {
            return [
                'ready' => false,
                'considerationCeiling' => 0,
                'pendingRanks' => [],
                'eligible' => [],
                'submitted' => $submitted,
            ];
        }

        $eligible = [];

        if ($existingClaim === null && $this->canPassOn($user, $weekStart)) {
            $rank = $this->userWeeklyRank($user, $weekStart);

            $eligible[] = [
                'rank' => $rank,
                ...$this->passDownContext($weekStart, $rank),
            ];
        }

        return [
            'ready' => true,
            'considerationCeiling' => $this->considerationCeiling($weekStart),
            'pendingRanks' => $this->pendingConsiderationRanks($weekStart),
            'eligible' => $eligible,
            'submitted' => $submitted,
        ];
    }

    /**
     * @param  array{week_start: string, passed_on: bool, preference?: string|null, pass_on_message?: string|null}  $data
     */
    public function submitClaim(User $user, array $data, bool $allowOutsideClaimWindow = false): WeeklyRewardClaim
    {
        $weekStart = $data['week_start'];
        $passedOn = filter_var($data['passed_on'], FILTER_VALIDATE_BOOLEAN);

        if (! $allowOutsideClaimWindow && ! $this->isWeekReadyForClaims($weekStart)) {
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

        if ($passedOn) {
            if (! $this->canPassOn($user, $weekStart)) {
                throw ValidationException::withMessages([
                    'week_start' => 'You are not currently eligible for this week\'s airtime.',
                ]);
            }
        } elseif (! $this->isUserEligible($user, $weekStart)) {
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
        return $this->canPassOn($user, $weekStart);
    }

    public function canPassOn(User $user, string $weekStart): bool
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

    private function claimWeekStart(): ?string
    {
        $now = Carbon::now(config('predictions.timezone'));

        if ($now->isSunday()) {
            return $now->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
        }

        if ($now->isMonday()) {
            return $now->copy()->startOfWeek(Carbon::MONDAY)->subWeek()->toDateString();
        }

        return null;
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
}
