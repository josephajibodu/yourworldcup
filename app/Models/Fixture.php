<?php

namespace App\Models;

use App\Enums\FixtureStage;
use App\Enums\FixtureStatus;
use App\Enums\HighestBookingOutcome;
use App\Enums\LastGoalOutcome;
use App\Enums\ResultDuration;
use Database\Factories\FixtureFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string|null $external_id
 * @property string|null $provider
 * @property string|null $provider_match_id
 * @property FixtureStage $stage
 * @property string|null $group_code
 * @property string|null $round_label
 * @property int|null $matchday
 * @property int|null $home_team_id
 * @property int|null $away_team_id
 * @property int|null $stadium_id
 * @property string|null $referee
 * @property Carbon $kickoff_at
 * @property Carbon $lock_at
 * @property FixtureStatus $status
 * @property int|null $home_score
 * @property int|null $away_score
 * @property int|null $extra_time_home
 * @property int|null $extra_time_away
 * @property int|null $penalties_home
 * @property int|null $penalties_away
 * @property ResultDuration|null $result_duration
 * @property LastGoalOutcome|null $last_goal
 * @property HighestBookingOutcome|null $highest_booking
 * @property int|null $winner_team_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Fixture extends Model
{
    /** @use HasFactory<FixtureFactory> */
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'stage' => FixtureStage::class,
            'status' => FixtureStatus::class,
            'result_duration' => ResultDuration::class,
            'last_goal' => LastGoalOutcome::class,
            'highest_booking' => HighestBookingOutcome::class,
            'matchday' => 'integer',
            'kickoff_at' => 'datetime',
            'lock_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function winnerTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'winner_team_id');
    }

    /**
     * @return HasMany<FixtureMarket, $this>
     */
    public function markets(): HasMany
    {
        return $this->hasMany(FixtureMarket::class);
    }

    /**
     * @return HasMany<BracketSlot, $this>
     */
    public function bracketSlots(): HasMany
    {
        return $this->hasMany(BracketSlot::class, 'feeds_fixture_id');
    }

    /**
     * @return BelongsTo<Stadium, $this>
     */
    public function stadium(): BelongsTo
    {
        return $this->belongsTo(Stadium::class);
    }

    public function isLocked(): bool
    {
        return now()->greaterThanOrEqualTo($this->lock_at);
    }

    public function isLive(): bool
    {
        if (in_array($this->status, [FixtureStatus::Final, FixtureStatus::Void], true)) {
            return false;
        }

        $windowEnd = $this->kickoff_at->copy()->addMinutes(
            (int) config('predictions.match_duration_minutes', 105),
        );

        $now = now();

        if ($now->greaterThanOrEqualTo($this->kickoff_at) && $now->lessThan($windowEnd)) {
            return true;
        }

        return $this->status === FixtureStatus::Live;
    }

    public static function findByProviderMatch(string $provider, string $providerMatchId): ?self
    {
        return self::query()
            ->where('provider', $provider)
            ->where('provider_match_id', $providerMatchId)
            ->first();
    }

    /**
     * The calendar day this fixture belongs to, in the platform's day-boundary
     * timezone (WAT) — the unit the daily prediction game and banker rule use.
     */
    public function watDate(): string
    {
        return $this->kickoff_at
            ->copy()
            ->setTimezone(config('predictions.timezone'))
            ->toDateString();
    }

    /**
     * @param  Builder<Fixture>  $query
     * @return Builder<Fixture>
     */
    public function scopeOnWatDate(Builder $query, string $watDate): Builder
    {
        $start = Carbon::parse($watDate, 'Africa/Lagos')->startOfDay()->utc();

        return $query->whereBetween('kickoff_at', [$start, $start->copy()->addDay()]);
    }
}
