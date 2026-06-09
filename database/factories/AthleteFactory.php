<?php

namespace Database\Factories;

use App\Enums\SportGender;
use App\Models\Athlete;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Athlete>
 */
class AthleteFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'user_id' => null,
            'name' => fake()->name(),
            'dob' => fake()->dateTimeBetween('-30 years', '-15 years'),
            'gender' => fake()->randomElement(SportGender::cases()),
            'nationality' => fake()->countryCode(),
            'id_number' => fake()->unique()->numerify('ID########'),
            'medical_clearance' => fake()->boolean(80),
            'weight' => fake()->randomFloat(2, 40, 120),
        ];
    }
}