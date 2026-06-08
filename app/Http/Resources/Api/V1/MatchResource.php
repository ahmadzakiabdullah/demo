<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\MatchGame */
class MatchResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'fixture_id' => $this->fixture_id,
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'duration_minutes' => $this->duration_minutes,
            'status' => $this->status->value,
            'notes' => $this->notes,
            'venue' => $this->whenLoaded('venue', fn () => $this->venue?->only(['id', 'name'])),
            'facility' => $this->whenLoaded('facility', fn () => $this->facility?->only(['id', 'name'])),
            'participants' => $this->whenLoaded('participants', fn () => $this->participants->map(fn ($participant) => [
                'side' => $participant->side->value,
                'participant_type' => $participant->participant_type,
                'participant_id' => $participant->participant_id,
                'name' => $participant->participant?->name,
            ])),
            'officials' => $this->whenLoaded('officials', fn () => $this->officials->map(fn ($assignment) => [
                'official_id' => $assignment->official_id,
                'role' => $assignment->role->value,
                'name' => $assignment->official?->name,
            ])),
        ];
    }
}