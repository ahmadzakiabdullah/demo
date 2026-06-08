<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Fixture */
class FixtureResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'competition_id' => $this->competition_id,
            'group_id' => $this->group_id,
            'name' => $this->name,
            'round' => $this->round,
            'sort_order' => $this->sort_order,
            'group' => $this->whenLoaded('group', fn () => $this->group?->only(['id', 'name', 'slug'])),
            'matches' => MatchResource::collection($this->whenLoaded('matches')),
        ];
    }
}