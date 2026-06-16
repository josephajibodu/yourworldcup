<?php

namespace Database\Factories;

use App\Enums\FixtureStage;
use App\Enums\FixtureStatus;
use App\Models\Fixture;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Fixture>
 */
class FixtureFactory extends Factory
{
    protected $model = Fixture::class;

    public function definition(): array
    {
        $kickoff = Carbon::instance(fake()->dateTimeBetween('+1 hour', '+10 days'));

        return [
            'stage' => FixtureStage::Group,
            'group_code' => 'A',
            'home_team_id' => Team::factory(),
            'away_team_id' => Team::factory(),
            'kickoff_at' => $kickoff,
            'lock_at' => $kickoff->copy()->subHour(),
            'status' => FixtureStatus::Scheduled,
        ];
    }

    public function final(int $home, int $away): static
    {
        return $this->state(fn (): array => [
            'status' => FixtureStatus::Final,
            'home_score' => $home,
            'away_score' => $away,
        ]);
    }

    public function locked(): static
    {
        $kickoff = now()->addMinutes(30);

        return $this->state(fn (): array => [
            'kickoff_at' => $kickoff,
            'lock_at' => $kickoff->copy()->subHour(),
        ]);
    }
}
