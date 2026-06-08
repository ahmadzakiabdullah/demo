<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Official */
class OfficialResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'user_id' => $this->user_id,
            'name' => $this->name,
            'email' => $this->email,
            'type' => $this->type->value,
            'certification_level' => $this->certification_level,
            'certification_expires_at' => $this->certification_expires_at?->toDateString(),
            'registrations' => $this->whenLoaded('registrations', fn () => RegistrationResource::collection($this->registrations)),
            'history' => $this->whenLoaded('history', fn () => RegistrationResource::collection($this->history)),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}