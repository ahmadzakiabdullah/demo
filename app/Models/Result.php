<?php

namespace App\Models;

use App\Enums\AppealStatus;
use App\Enums\ResultStatus;
use App\Models\Concerns\Auditable;
use Database\Factories\ResultFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'match_id',
    'entered_by',
    'data',
    'status',
    'confirmed_by',
    'confirmed_at',
    'published_at',
    'notes',
])]
class Result extends Model
{
    /** @use HasFactory<ResultFactory> */
    use Auditable, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data' => 'array',
            'status' => ResultStatus::class,
            'confirmed_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(MatchGame::class, 'match_id');
    }

    public function enteredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'entered_by');
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function appeals(): HasMany
    {
        return $this->hasMany(ResultAppeal::class);
    }

    public function openAppeal(): ?ResultAppeal
    {
        return $this->appeals()
            ->whereIn('status', [
                AppealStatus::Submitted->value,
                AppealStatus::UnderReview->value,
            ])
            ->latest()
            ->first();
    }

    public function winnerSide(): ?string
    {
        return $this->data['winner_side'] ?? null;
    }

    public function resolveAuditOrganizationId(): ?int
    {
        return $this->match?->competition()?->organization_id;
    }
}