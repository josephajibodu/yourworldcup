<?php

namespace App\Models;

use Database\Factories\TeamFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string|null $external_id
 * @property string|null $provider
 * @property string|null $provider_team_id
 * @property string $name
 * @property string $code
 * @property string|null $iso2
 * @property string|null $group_code
 * @property string|null $confederation
 * @property bool $is_african
 * @property string|null $flag
 * @property Carbon|null $eliminated_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Team extends Model
{
    /** @use HasFactory<TeamFactory> */
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_african' => 'boolean',
            'eliminated_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<Fixture, $this>
     */
    public function homeFixtures(): HasMany
    {
        return $this->hasMany(Fixture::class, 'home_team_id');
    }

    /**
     * @return HasMany<Fixture, $this>
     */
    public function awayFixtures(): HasMany
    {
        return $this->hasMany(Fixture::class, 'away_team_id');
    }

    /**
     * @param  Builder<Team>  $query
     * @return Builder<Team>
     */
    public function scopeAfrican(Builder $query): Builder
    {
        return $query->where('is_african', true);
    }

    public function isEliminated(): bool
    {
        return $this->eliminated_at !== null;
    }

    public static function findByProviderTeam(string $provider, string $providerTeamId): ?self
    {
        return self::query()
            ->where('provider', $provider)
            ->where('provider_team_id', $providerTeamId)
            ->first();
    }
}
