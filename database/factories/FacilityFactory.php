<?php

namespace Database\Factories;

use App\Enums\FacilityType;
use App\Models\Facility;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Facility>
 */
class FacilityFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'venue_id' => Venue::factory(),
            'name' => ucfirst($name),
            'slug' => Str::slug($name),
            'type' => fake()->randomElement(FacilityType::cases()),
            'capacity' => fake()->optional()->numberBetween(10, 5000),
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }
}