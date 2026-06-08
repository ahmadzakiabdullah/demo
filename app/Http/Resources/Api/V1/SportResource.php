<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Sport */
class SportResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'status' => $this->status->value,
            'template_slug' => $this->template_slug,
            'rules' => $this->rules,
            'disciplines' => $this->whenLoaded('disciplines', fn () => $this->disciplines->map(fn ($discipline) => [
                'id' => $discipline->id,
                'name' => $discipline->name,
                'slug' => $discipline->slug,
                'categories' => $discipline->relationLoaded('categories')
                    ? $discipline->categories->map(fn ($category) => [
                        'id' => $category->id,
                        'name' => $category->name,
                        'slug' => $category->slug,
                        'gender' => $category->gender->value,
                        'min_age' => $category->min_age,
                        'max_age' => $category->max_age,
                        'divisions' => $category->relationLoaded('divisions')
                            ? $category->divisions->map(fn ($division) => [
                                'id' => $division->id,
                                'name' => $division->name,
                                'slug' => $division->slug,
                            ])
                            : [],
                    ])
                    : [],
            ])),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}