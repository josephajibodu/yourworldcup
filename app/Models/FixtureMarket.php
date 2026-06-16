<?php

namespace App\Models;

use App\Enums\MarketStatus;
use Carbon\CarbonInterface;
use Database\Factories\FixtureMarketFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $fixture_id
 * @property int $prediction_market_id
 * @property bool $is_enabled
 * @property int|null $base_points
 * @property Carbon|null $lock_at
 * @property array<int, mixed>|null $options
 * @property array<string, mixed>|null $settlement_value
 * @property MarketStatus $status
 * @property Carbon|null $settled_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class FixtureMarket extends Model
{
    /** @use HasFactory<FixtureMarketFactory> */
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'base_points' => 'integer',
            'lock_at' => 'datetime',
            'options' => 'array',
            'settlement_value' => 'array',
            'status' => MarketStatus::class,
            'settled_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Fixture, $this>
     */
    public function fixture(): BelongsTo
    {
        return $this->belongsTo(Fixture::class);
    }

    /**
     * @return BelongsTo<PredictionMarket, $this>
     */
    public function market(): BelongsTo
    {
        return $this->belongsTo(PredictionMarket::class, 'prediction_market_id');
    }

    /**
     * @return HasMany<Prediction, $this>
     */
    public function predictions(): HasMany
    {
        return $this->hasMany(Prediction::class);
    }

    /**
     * Points this market is worth on this fixture — the per-fixture override
     * if present, otherwise the market's catalogue value.
     */
    public function effectiveBasePoints(): int
    {
        return $this->base_points ?? $this->market->base_points;
    }

    public function effectiveLockAt(): CarbonInterface
    {
        return $this->lock_at ?? $this->fixture->lock_at;
    }

    /**
     * Whether a user may still create or edit a prediction on this market:
     * the market is enabled, open, and its lock time has not passed.
     */
    public function isOpenForPrediction(): bool
    {
        return $this->is_enabled
            && $this->status === MarketStatus::Open
            && now()->lessThan($this->effectiveLockAt());
    }

    /**
     * Per-fixture resolved options (e.g. squad lists) override the market's
     * static option set when present.
     *
     * @return array<int, mixed>|null
     */
    public function resolvedOptions(): ?array
    {
        return $this->options ?? $this->market->options;
    }
}
