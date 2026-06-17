<?php

namespace App\Models;

use App\Enums\BracketSlotSide;
use App\Enums\BracketSlotType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property BracketSlotSide $side
 * @property BracketSlotType $slot_type
 * @property array<string, mixed> $slot_spec
 * @property string|null $label
 * @property int|null $resolved_team_id
 * @property int|null $source_fixture_id
 * @property int|null $feeds_fixture_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class BracketSlot extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'side' => BracketSlotSide::class,
            'slot_type' => BracketSlotType::class,
            'slot_spec' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function resolvedTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'resolved_team_id');
    }

    /**
     * @return BelongsTo<Fixture, $this>
     */
    public function sourceFixture(): BelongsTo
    {
        return $this->belongsTo(Fixture::class, 'source_fixture_id');
    }

    /**
     * @return BelongsTo<Fixture, $this>
     */
    public function feedsFixture(): BelongsTo
    {
        return $this->belongsTo(Fixture::class, 'feeds_fixture_id');
    }

    public function displayCode(): string
    {
        return match ($this->slot_type) {
            BracketSlotType::GroupWinner => '1'.($this->slot_spec['group'] ?? '?'),
            BracketSlotType::GroupRunnerUp => '2'.($this->slot_spec['group'] ?? '?'),
            BracketSlotType::BestThird => '3rd '.implode('/', $this->slot_spec['groups'] ?? []),
            BracketSlotType::KnockoutWinner => 'W'.($this->slot_spec['match'] ?? '?'),
            BracketSlotType::KnockoutLoser => 'RU'.($this->slot_spec['match'] ?? '?'),
        };
    }
}
