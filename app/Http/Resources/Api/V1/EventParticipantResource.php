<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\EventParticipant */
class EventParticipantResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'event_id' => $this->event_id,
            'branch_id' => $this->branch_id,
            'type' => $this->type->value,
            'name' => $this->name,
            'code' => $this->code,
            'status' => $this->status->value,
            'metadata' => $this->metadata,
            'sport_entries_count' => $this->whenCounted('sportEntries'),
            'teams_count' => $this->whenCounted('teams'),
            'athletes_count' => $this->whenCounted('athletes'),
            'branch' => $this->whenLoaded('branch', fn () => $this->branch?->only(['id', 'name', 'code'])),
            'sport_entries' => $this->whenLoaded('sportEntries', fn () => ParticipantSportEntryResource::collection($this->sportEntries)),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}