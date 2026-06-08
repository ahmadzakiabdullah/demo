<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Venue */
class VenueResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'address' => $this->address,
            'capacity' => $this->capacity,
            'timezone' => $this->timezone,
            'notes' => $this->notes,
            'facilities' => FacilityResource::collection($this->whenLoaded('facilities')),
            'pivot' => $this->whenPivotLoaded('event_venue', fn () => [
                'is_primary' => (bool) $this->pivot->is_primary,
                'notes' => $this->pivot->notes,
            ]),
            'sports' => $this->whenLoaded('sports', fn () => $this->sports->map(fn ($sport) => [
                'id' => $sport->id,
                'name' => $sport->name,
                'slug' => $sport->slug,
            ])),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}