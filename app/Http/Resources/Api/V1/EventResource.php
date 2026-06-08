<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Event */
class EventResource extends JsonResource
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
            'location' => $this->location,
            'description' => $this->description,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'organization' => new OrganizationResource($this->whenLoaded('organization')),
            'event_type' => $this->whenLoaded('eventType', fn () => $this->eventType?->only(['id', 'name', 'slug'])),
            'event_category' => $this->whenLoaded('eventCategory', fn () => $this->eventCategory?->only(['id', 'name', 'slug'])),
            'assignees' => $this->whenLoaded('assignees', fn () => $this->assignees->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->pivot->role,
            ])),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}