<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Registration */
class RegistrationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event_id' => $this->event_id,
            'sport_id' => $this->sport_id,
            'status' => $this->status->value,
            'notes' => $this->notes,
            'rejected_reason' => $this->rejected_reason,
            'sport_category_id' => $this->sport_category_id,
            'sport_division_id' => $this->sport_division_id,
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'verified_at' => $this->verified_at?->toIso8601String(),
            'approved_at' => $this->approved_at?->toIso8601String(),
            'sport' => $this->whenLoaded('sport', fn () => [
                'id' => $this->sport->id,
                'name' => $this->sport->name,
                'slug' => $this->sport->slug,
            ]),
            'sport_category' => $this->whenLoaded('sportCategory', fn () => $this->sportCategory?->only(['id', 'name', 'slug'])),
            'sport_division' => $this->whenLoaded('sportDivision', fn () => $this->sportDivision?->only(['id', 'name', 'slug'])),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}