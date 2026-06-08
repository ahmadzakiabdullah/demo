<?php

namespace Database\Factories;

use App\Enums\OrganizationStatus;
use App\Enums\OrganizationType;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Organization>
 */
class OrganizationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'type' => OrganizationType::University,
            'timezone' => 'Asia/Kuala_Lumpur',
            'locale' => 'en',
            'status' => OrganizationStatus::Active,
        ];
    }

    public function university(): static
    {
        return $this->state(fn () => ['type' => OrganizationType::University]);
    }

    public function suspended(): static
    {
        return $this->state(fn () => ['status' => OrganizationStatus::Suspended]);
    }
}