<?php

namespace App\Models;

use App\Enums\FixtureStage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property FixtureStage $stage
 * @property int $position
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
            'stage' => FixtureStage::class,
            'position' => 'integer',
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
}
