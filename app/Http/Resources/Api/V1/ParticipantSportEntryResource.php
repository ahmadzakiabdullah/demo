<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\ParticipantSportEntry */
class ParticipantSportEntryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event_participant_id' => $this->event_participant_id,
            'sport_id' => $this->sport_id,
            'sport_category_id' => $this->sport_category_id,
            'sport_division_id' => $this->sport_division_id,
            'status' => $this->status->value,
            'notes' => $this->notes,
            'rejected_reason' => $this->rejected_reason,
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'approved_at' => $this->approved_at?->toIso8601String(),
            'sport' => $this->whenLoaded('sport', fn () => $this->sport?->only(['id', 'name', 'slug'])),
            'sport_category' => $this->whenLoaded('sportCategory', fn () => $this->sportCategory?->only(['id', 'name'])),
            'sport_division' => $this->whenLoaded('sportDivision', fn () => $this->sportDivision?->only(['id', 'name'])),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}