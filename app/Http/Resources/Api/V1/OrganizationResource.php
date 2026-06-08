<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Organization */
class OrganizationResource extends JsonResource
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
            'type' => $this->type?->value,
            'status' => $this->status?->value,
            'timezone' => $this->when(isset($this->timezone), $this->timezone),
            'locale' => $this->when(isset($this->locale), $this->locale),
            'branches_count' => $this->whenCounted('branches'),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}