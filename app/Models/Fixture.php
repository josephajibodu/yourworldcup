<?php

namespace App\Models;

use App\Enums\FixtureStage;
use App\Enums\FixtureStatus;
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
 * @property FixtureStage $stage
 * @property string|null $group_code
 * @property string|null $round_label
 * @property int|null $matchday
 * @property int|null $home_team_id
 * @property int|null $away_team_id
 * @property int|null $stadium_id
 * @property Carbon $kickoff_at
 * @property Carbon $lock_at
 * @property FixtureStatus $status
 * @property int|null $home_score
 * @property int|null $away_score
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
