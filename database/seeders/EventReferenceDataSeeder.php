<?php

namespace Database\Seeders;

use App\Models\EventCategory;
use App\Models\EventType;
use Illuminate\Database\Seeder;

class EventReferenceDataSeeder extends Seeder
{
    /**
     * Seed global event types and categories.
     */
    public function run(): void
    {
        $types = [
            ['name' => 'Multi-Sport Games', 'slug' => 'multi-sport'],
            ['name' => 'Single-Sport Tournament', 'slug' => 'tournament'],
            ['name' => 'League Season', 'slug' => 'league'],
            ['name' => 'Friendly Match', 'slug' => 'friendly'],
        ];

        foreach ($types as $type) {
            EventType::query()->firstOrCreate(['slug' => $type['slug']], $type);
        }

        $categories = [
            ['name' => 'School', 'slug' => 'school'],
            ['name' => 'University', 'slug' => 'university'],
            ['name' => 'Elite', 'slug' => 'elite'],
        ];

        foreach ($categories as $category) {
            EventCategory::query()->firstOrCreate(['slug' => $category['slug']], $category);
        }
    }
}