<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Database\Factories\CompetitionParticipantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'competition_id',
    'participant_type',
    'participant_id',
    'seed',
    'ladder_rank',
    'swiss_points',
    'swiss_buchholz',
])]
class CompetitionParticipant extends Model
{
    /** @use HasFactory<CompetitionParticipantFactory> */
    use Auditable, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'swiss_points' => 'decimal:1',
        ];
    }

    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    public function participant(): MorphTo
    {
        return $this->morphTo();
    }

    public function resolveAuditOrganizationId(): ?int
    {
        return $this->competition?->organization_id;
    }
}