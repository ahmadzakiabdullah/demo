<?php

namespace App\Models;

use App\Enums\AppealStatus;
use App\Models\Concerns\Auditable;
use Database\Factories\ResultAppealFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'organization_id',
    'result_id',
    'submitted_by',
    'reason',
    'status',
    'proposed_home_score',
    'proposed_away_score',
    'reviewed_by',
    'reviewed_at',
    'resolution_notes',
])]
class ResultAppeal extends Model
{
    /** @use HasFactory<ResultAppealFactory> */
    use Auditable, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => AppealStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function result(): BelongsTo
    {
        return $this->belongsTo(Result::class);
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function resolveAuditOrganizationId(): ?int
    {
        return $this->organization_id;
    }
}