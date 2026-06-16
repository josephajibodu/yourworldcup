<?php

namespace Database\Factories;

use App\Models\Stadium;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Stadium>
 */
class StadiumFactory extends Factory
{
    protected $model = Stadium::class;

    public function definition(): array
    {
        return [
            'external_id' => (string) fake()->unique()->numberBetween(1, 100000),
            'name' => fake()->city().' Stadium',
            'fifa_name' => null,
            'city' => fake()->city(),
            'country' => fake()->randomElement(['United States', 'Canada', 'Mexico']),
            'capacity' => fake()->numberBetween(30000, 90000),
            'region' => fake()->randomElement(['Eastern', 'Central', 'Western']),
            'timezone' => 'America/New_York',
        ];
    }
}
