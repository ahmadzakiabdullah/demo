<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Organization;
use App\Models\Sport;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Team>
 */
class TeamFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'organization_id' => Organization::factory(),
            'event_id' => Event::factory(),
            'sport_id' => Sport::factory(),
            'name' => ucfirst($name),
            'slug' => Str::slug($name),
            'coach_user_id' => null,
            'manager_user_id' => null,
            'notes' => null,
        ];
    }
}