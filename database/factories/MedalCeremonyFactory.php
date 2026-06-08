<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\MedalCeremony;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MedalCeremony> */
class MedalCeremonyFactory extends Factory
{
    protected $model = MedalCeremony::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'event_id' => Event::factory(),
            'name' => fake()->words(3, true),
            'scheduled_at' => now()->addDays(3),
            'duration_minutes' => 60,
        ];
    }
}