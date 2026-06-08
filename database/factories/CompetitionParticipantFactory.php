<?php

namespace Database\Factories;

use App\Models\Competition;
use App\Models\CompetitionParticipant;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CompetitionParticipant> */
class CompetitionParticipantFactory extends Factory
{
    protected $model = CompetitionParticipant::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'competition_id' => Competition::factory(),
            'participant_type' => Team::class,
            'participant_id' => Team::factory(),
            'seed' => fake()->numberBetween(1, 16),
            'ladder_rank' => fake()->numberBetween(1, 16),
        ];
    }
}