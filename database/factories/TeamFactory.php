<?php

namespace Database\Factories;

use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Team>
 */
class TeamFactory extends Factory
{
    protected $model = Team::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->country(),
            'code' => strtoupper(fake()->unique()->lexify('???')),
            'group_code' => fake()->randomElement(['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L']),
            'confederation' => fake()->randomElement(['CAF', 'UEFA', 'CONMEBOL', 'CONCACAF', 'AFC', 'OFC']),
            'is_african' => false,
            'flag' => null,
        ];
    }

    public function african(): static
    {
        return $this->state(fn (): array => [
            'is_african' => true,
            'confederation' => 'CAF',
        ]);
    }

    public function eliminated(): static
    {
        return $this->state(fn (): array => ['eliminated_at' => now()]);
    }
}
