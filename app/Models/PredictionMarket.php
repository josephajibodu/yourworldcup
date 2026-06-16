<?php

namespace App\Models;

use App\Enums\MarketInputType;
use Database\Factories\PredictionMarketFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $key
 * @property string $name
 * @property string|null $description
 * @property MarketInputType $input_type
 * @property array<int, mixed>|null $options
 * @property int $base_points
 * @property string $scorer
 * @property bool $is_default
 * @property bool $is_enabled
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class PredictionMarket extends Model
{
    /** @use HasFactory<PredictionMarketFactory> */
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'input_type' => MarketInputType::class,
            'options' => 'array',
            'base_points' => 'integer',
            'is_default' => 'boolean',
            'is_enabled' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return HasMany<FixtureMarket, $this>
     */
    public function fixtureMarkets(): HasMany
    {
        return $this->hasMany(FixtureMarket::class);
    }

    /**
     * @param  Builder<PredictionMarket>  $query
     * @return Builder<PredictionMarket>
     */
    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('is_enabled', true);
    }

    /**
     * @param  Builder<PredictionMarket>  $query
     * @return Builder<PredictionMarket>
     */
    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }
}
