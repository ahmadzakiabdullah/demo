<?php

namespace Database\Factories;

use App\Enums\AppealStatus;
use App\Models\Organization;
use App\Models\Result;
use App\Models\ResultAppeal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ResultAppeal> */
class ResultAppealFactory extends Factory
{
    protected $model = ResultAppeal::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'result_id' => Result::factory(),
            'submitted_by' => User::factory(),
            'reason' => fake()->sentence(),
            'status' => AppealStatus::Submitted,
            'proposed_home_score' => fake()->numberBetween(0, 5),
            'proposed_away_score' => fake()->numberBetween(0, 5),
        ];
    }
}