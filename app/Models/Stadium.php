<?php

namespace App\Models;

use Database\Factories\StadiumFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string|null $external_id
 * @property string $name
 * @property string|null $fifa_name
 * @property string|null $city
 * @property string|null $country
 * @property int|null $capacity
 * @property string|null $region
 * @property string|null $timezone
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Stadium extends Model
{
    /** @use HasFactory<StadiumFactory> */
    use HasFactory;

    protected $table = 'stadiums';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
        ];
    }

    /**
     * @return HasMany<Fixture, $this>
     */
    public function fixtures(): HasMany
    {
        return $this->hasMany(Fixture::class);
    }
}
