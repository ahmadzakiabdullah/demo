<?php

namespace Database\Factories;

use App\Enums\EventParticipantStatus;
use App\Enums\EventParticipantType;
use App\Models\Event;
use App\Models\EventParticipant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventParticipant>
 */
class EventParticipantFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->city();
        $event = Event::factory()->create();

        return [
            'organization_id' => $event->organization_id,
            'event_id' => $event->id,
            'type' => EventParticipantType::State,
            'name' => $name,
            'code' => strtoupper(fake()->unique()->lexify('???')),
            'status' => EventParticipantStatus::Active,
            'metadata' => null,
        ];
    }
}