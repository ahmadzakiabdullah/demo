<?php

namespace Database\Factories;

use App\Enums\CompetitionStatus;
use App\Models\Competition;
use App\Models\CompetitionFormat;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Sport;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Competition>
 */
class CompetitionFactory extends Factory
{
    protected $model = Competition::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'organization_id' => Organization::factory(),
            'event_id' => Event::factory(),
            'sport_id' => Sport::factory(),
            'competition_format_id' => CompetitionFormat::query()->inRandomOrder()->value('id')
                ?? CompetitionFormat::query()->create([
                    'name' => 'Round Robin',
                    'slug' => 'round_robin',
                    'sort_order' => 2,
                ])->id,
            'name' => ucwords($name),
            'slug' => Str::slug($name),
            'status' => CompetitionStatus::Draft,
            'notes' => null,
        ];
    }
}