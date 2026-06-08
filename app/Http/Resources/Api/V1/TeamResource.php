<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Team */
class TeamResource extends JsonResource
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
            'notes' => $this->notes,
            'coach_user_id' => $this->coach_user_id,
            'manager_user_id' => $this->manager_user_id,
            'coach' => $this->whenLoaded('coach', fn () => $this->coach?->only(['id', 'name', 'email'])),
            'manager' => $this->whenLoaded('manager', fn () => $this->manager?->only(['id', 'name', 'email'])),
            'sport' => $this->whenLoaded('sport', fn () => $this->sport?->only(['id', 'name', 'slug'])),
            'athletes' => $this->whenLoaded('athletes', fn () => $this->athletes->map(fn ($athlete) => [
                'id' => $athlete->id,
                'name' => $athlete->name,
                'role' => $athlete->pivot->role,
                'jersey_number' => $athlete->pivot->jersey_number,
            ])),
            'registrations' => $this->whenLoaded('registrations', fn () => RegistrationResource::collection($this->registrations)),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}