<?php

namespace Database\Factories;

use App\Enums\OfficialType;
use App\Models\Official;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Official>
 */
class OfficialFactory extends Factory
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
            'email' => fake()->unique()->safeEmail(),
            'type' => fake()->randomElement(OfficialType::cases()),
            'certification_level' => fake()->randomElement(['Level 1', 'Level 2', 'National', 'International']),
            'certification_expires_at' => fake()->dateTimeBetween('+1 month', '+2 years'),
        ];
    }

    public function expiredCertification(): static
    {
        return $this->state(fn () => [
            'certification_expires_at' => fake()->dateTimeBetween('-2 years', '-1 day'),
        ]);
    }
}