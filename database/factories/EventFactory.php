<?php

namespace Database\Factories;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\EventType;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'organization_id' => Organization::factory(),
            'event_type_id' => EventType::query()->first()?->id ?? 1,
            'event_category_id' => EventCategory::query()->first()?->id ?? 1,
            'name' => ucfirst($name),
            'slug' => Str::slug($name),
            'edition_year' => (int) fake()->year(),
            'status' => EventStatus::Draft,
            'location' => fake()->city(),
            'description' => fake()->sentence(),
            'starts_at' => now()->addWeek(),
            'ends_at' => now()->addWeeks(2),
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => ['status' => EventStatus::Published]);
    }

    public function active(): static
    {
        return $this->state(fn () => ['status' => EventStatus::Active]);
    }
}