<?php

namespace Database\Factories;

use App\Enums\EventCadence;
use App\Models\EventSeries;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<EventSeries>
 */
class EventSeriesFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'organization_id' => Organization::factory(),
            'name' => ucfirst($name),
            'slug' => Str::slug($name),
            'cadence' => EventCadence::Annual,
            'description' => fake()->sentence(),
        ];
    }
}