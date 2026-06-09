<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventParticipant;
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
        $event = Event::factory()->create();

        return [
            'organization_id' => $event->organization_id,
            'event_participant_id' => EventParticipant::factory()->create([
                'organization_id' => $event->organization_id,
                'event_id' => $event->id,
            ])->id,
            'event_id' => $event->id,
            'sport_id' => Sport::factory()->create(['event_id' => $event->id])->id,
            'name' => ucfirst($name),
            'slug' => Str::slug($name),
            'coach_user_id' => null,
            'manager_user_id' => null,
            'notes' => null,
        ];
    }
}