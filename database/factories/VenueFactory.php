<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Venue>
 */
class VenueFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company().' Arena';

        return [
            'organization_id' => Organization::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'address' => fake()->address(),
            'capacity' => fake()->numberBetween(100, 50000),
            'timezone' => 'UTC',
            'notes' => fake()->optional()->sentence(),
        ];
    }
}