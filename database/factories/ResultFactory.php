<?php

namespace Database\Factories;

use App\Enums\ResultStatus;
use App\Models\MatchGame;
use App\Models\Result;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Result>
 */
class ResultFactory extends Factory
{
    protected $model = Result::class;

    public function definition(): array
    {
        $home = fake()->numberBetween(0, 5);
        $away = fake()->numberBetween(0, 5);

        return [
            'match_id' => MatchGame::factory(),
            'entered_by' => null,
            'data' => [
                'home_score' => $home,
                'away_score' => $away,
                'winner_side' => $home === $away ? null : ($home > $away ? 'home' : 'away'),
            ],
            'status' => ResultStatus::Pending,
            'confirmed_by' => null,
            'confirmed_at' => null,
            'published_at' => null,
            'notes' => null,
        ];
    }
}