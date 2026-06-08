<?php

namespace Database\Factories;

use App\Enums\MatchStatus;
use App\Models\Fixture;
use App\Models\MatchGame;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MatchGame>
 */
class MatchGameFactory extends Factory
{
    protected $model = MatchGame::class;

    public function definition(): array
    {
        return [
            'fixture_id' => Fixture::factory(),
            'venue_id' => null,
            'facility_id' => null,
            'scheduled_at' => fake()->dateTimeBetween('+1 day', '+30 days'),
            'duration_minutes' => 60,
            'status' => MatchStatus::Scheduled,
            'notes' => null,
        ];
    }
}