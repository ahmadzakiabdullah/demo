<?php

namespace App\Models;

use App\Enums\RegistrationStatus;
use App\Models\Concerns\Auditable;
use Database\Factories\RegistrationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'event_id',
    'sport_id',
    'registrable_type',
    'registrable_id',
    'sport_category_id',
    'sport_division_id',
    'status',
    'notes',
    'rejected_reason',
    'submitted_at',
    'verified_at',
    'approved_at',
])]
class Registration extends Model
{
    /** @use HasFactory<RegistrationFactory> */
    use Auditable, HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => RegistrationStatus::class,
            'submitted_at' => 'datetime',
            'verified_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function sport(): BelongsTo
    {
        return $this->belongsTo(Sport::class);
    }

    public function registrable(): MorphTo
    {
        return $this->morphTo();
    }

    public function sportCategory(): BelongsTo
    {
        return $this->belongsTo(SportCategory::class);
    }

    public function sportDivision(): BelongsTo
    {
        return $this->belongsTo(SportDivision::class);
    }

    public function resolveAuditOrganizationId(): ?int
    {
        return $this->event?->organization_id;
    }
}