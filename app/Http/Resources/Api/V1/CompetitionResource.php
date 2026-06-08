<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Competition */
class CompetitionResource extends JsonResource
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
            'sport_id' => $this->sport_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'status' => $this->status->value,
            'notes' => $this->notes,
            'sport' => $this->whenLoaded('sport', fn () => $this->sport?->only(['id', 'name', 'slug'])),
            'format' => $this->whenLoaded('format', fn () => $this->format?->only(['id', 'name', 'slug'])),
            'fixtures' => FixtureResource::collection($this->whenLoaded('fixtures')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}