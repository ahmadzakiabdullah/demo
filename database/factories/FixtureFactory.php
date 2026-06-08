<?php

namespace Database\Factories;

use App\Models\Competition;
use App\Models\Fixture;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Fixture>
 */
class FixtureFactory extends Factory
{
    protected $model = Fixture::class;

    public function definition(): array
    {
        return [
            'competition_id' => Competition::factory(),
            'group_id' => null,
            'name' => 'Match Day '.fake()->numberBetween(1, 10),
            'round' => 'Round '.fake()->numberBetween(1, 5),
            'sort_order' => fake()->numberBetween(0, 20),
        ];
    }
}