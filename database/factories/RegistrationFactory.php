<?php

namespace Database\Factories;

use App\Enums\RegistrationStatus;
use App\Models\Athlete;
use App\Models\Event;
use App\Models\Registration;
use App\Models\Sport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Registration>
 */
class RegistrationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'sport_id' => Sport::factory(),
            'registrable_type' => Athlete::class,
            'registrable_id' => Athlete::factory(),
            'sport_category_id' => null,
            'sport_division_id' => null,
            'status' => RegistrationStatus::Draft,
            'notes' => null,
            'rejected_reason' => null,
        ];
    }
}