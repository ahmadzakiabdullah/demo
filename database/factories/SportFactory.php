<?php

namespace Database\Factories;

use App\Enums\SportStatus;
use App\Models\Event;
use App\Models\Sport;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Sport>
 */
class SportFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'event_id' => Event::factory(),
            'name' => ucfirst($name),
            'slug' => Str::slug($name),
            'template_slug' => null,
            'status' => SportStatus::Active,
            'rules' => ['format' => 'team'],
        ];
    }
}