<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\ResultAppeal */
class ResultAppealResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'result_id' => $this->result_id,
            'reason' => $this->reason,
            'status' => $this->status->value,
            'proposed_home_score' => $this->proposed_home_score,
            'proposed_away_score' => $this->proposed_away_score,
            'resolution_notes' => $this->resolution_notes,
            'submitted_by' => $this->submitted_by,
            'reviewed_by' => $this->reviewed_by,
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}