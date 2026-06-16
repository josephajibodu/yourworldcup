<?php

namespace App\Models;

use Database\Factories\PredictionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $fixture_market_id
 * @property array<string, mixed> $value
 * @property bool $is_banker
 * @property int|null $points_awarded
 * @property Carbon|null $scored_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Prediction extends Model
{
    /** @use HasFactory<PredictionFactory> */
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'value' => 'array',
            'is_banker' => 'boolean',
            'points_awarded' => 'integer',
            'scored_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<FixtureMarket, $this>
     */
    public function fixtureMarket(): BelongsTo
    {
        return $this->belongsTo(FixtureMarket::class);
    }
}
