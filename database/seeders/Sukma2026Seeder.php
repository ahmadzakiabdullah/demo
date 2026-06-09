<?php

namespace Database\Seeders;

use Database\Seeders\Support\Sukma2026Generator;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class Sukma2026Seeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $force = filter_var(env('SUKMA_SEED_FORCE', false), FILTER_VALIDATE_BOOL);
        $stats = (new Sukma2026Generator)->run($force);

        $this->command?->info('SUKMA Selangor 2026 seed completed.');
        $this->command?->table(
            ['Metric', 'Count'],
            collect($stats)
                ->except(['medal_tally_by_contingent', 'event'])
                ->map(fn ($value, $key) => ['metric' => str_replace('_', ' ', $key), 'count' => is_array($value) ? count($value) : $value])
                ->values()
                ->all(),
        );

        $this->command?->info('Summary written to database/seeders/data/sukma2026/summary.json');
        $this->command?->info('CSV samples written to database/seeders/data/sukma2026/samples/');
    }
}