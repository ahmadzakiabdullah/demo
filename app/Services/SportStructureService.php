<?php

namespace App\Services;

use App\Enums\SportGender;
use App\Models\Sport;
use App\Models\SportCategory;
use App\Models\SportDiscipline;
use App\Models\SportDivision;
use App\Support\SportTemplates;

class SportStructureService
{
    public function applyTemplate(Sport $sport, string $templateSlug): void
    {
        $template = SportTemplates::find($templateSlug);

        if ($template === null) {
            return;
        }

        foreach ($template['disciplines'] as $disciplineIndex => $disciplineData) {
            $discipline = SportDiscipline::query()->create([
                'sport_id' => $sport->id,
                'name' => $disciplineData['name'],
                'slug' => $disciplineData['slug'],
                'sort_order' => $disciplineIndex,
            ]);

            foreach ($disciplineData['categories'] as $categoryIndex => $categoryData) {
                $category = SportCategory::query()->create([
                    'sport_discipline_id' => $discipline->id,
                    'name' => $categoryData['name'],
                    'slug' => $categoryData['slug'],
                    'gender' => SportGender::from($categoryData['gender']),
                    'sort_order' => $categoryIndex,
                ]);

                foreach ($categoryData['divisions'] as $divisionIndex => $divisionData) {
                    SportDivision::query()->create([
                        'sport_category_id' => $category->id,
                        'name' => $divisionData['name'],
                        'slug' => $divisionData['slug'],
                        'sort_order' => $divisionIndex,
                    ]);
                }
            }
        }
    }
}