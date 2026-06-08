<?php

namespace Database\Seeders;

use App\Models\CompetitionFormat;
use Illuminate\Database\Seeder;

class CompetitionFormatsSeeder extends Seeder
{
    public function run(): void
    {
        $formats = [
            ['name' => 'League', 'slug' => 'league', 'description' => 'Season-style league with home and away fixtures', 'sort_order' => 1],
            ['name' => 'Round Robin', 'slug' => 'round_robin', 'description' => 'Each participant plays every other participant once', 'sort_order' => 2],
            ['name' => 'Knockout', 'slug' => 'knockout', 'description' => 'Single-elimination bracket', 'sort_order' => 3],
            ['name' => 'Double Elimination', 'slug' => 'double_elimination', 'description' => 'Winners and losers brackets with grand final', 'sort_order' => 4],
            ['name' => 'Group Stage', 'slug' => 'group_stage', 'description' => 'Round robin within groups followed by knockout', 'sort_order' => 5],
            ['name' => 'Swiss System', 'slug' => 'swiss', 'description' => 'Swiss pairing rounds with standings by points', 'sort_order' => 6],
            ['name' => 'Ladder', 'slug' => 'ladder', 'description' => 'Challenge ladder ranked by position', 'sort_order' => 7],
        ];

        foreach ($formats as $format) {
            CompetitionFormat::query()->firstOrCreate(
                ['slug' => $format['slug']],
                $format,
            );
        }
    }
}