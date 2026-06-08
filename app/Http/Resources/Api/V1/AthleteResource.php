<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Athlete */
class AthleteResource extends JsonResource
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
            'dob' => $this->dob?->toDateString(),
            'gender' => $this->gender?->value,
            'nationality' => $this->nationality,
            'id_number' => $this->id_number,
            'medical_clearance' => $this->medical_clearance,
            'registrations' => $this->whenLoaded('registrations', fn () => RegistrationResource::collection($this->registrations)),
            'history' => $this->whenLoaded('history', fn () => RegistrationResource::collection($this->history)),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}