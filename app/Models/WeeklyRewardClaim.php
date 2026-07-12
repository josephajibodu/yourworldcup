<?php

namespace App\Models;

use App\Enums\MobileNetwork;
use App\Enums\RewardPreference;
use Database\Factories\WeeklyRewardClaimFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $week_start
 * @property int $user_id
 * @property int $reward_position
 * @property int $leaderboard_rank
 * @property RewardPreference|null $preference
 * @property string|null $phone_number
 * @property MobileNetwork|null $mobile_network
 * @property string|null $account_holder_name
 * @property string|null $bank_name
 * @property string|null $account_number
 * @property bool $passed_on
 * @property string|null $pass_on_message
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class WeeklyRewardClaim extends Model
{
    /** @use HasFactory<WeeklyRewardClaimFactory> */
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'week_start' => 'date:Y-m-d',
            'reward_position' => 'integer',
            'leaderboard_rank' => 'integer',
            'preference' => RewardPreference::class,
            'mobile_network' => MobileNetwork::class,
            'passed_on' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
