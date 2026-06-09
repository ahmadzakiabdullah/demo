<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'organization_id',
    'event_id',
    'accreditable_type',
    'accreditable_id',
    'type',
    'qr_code',
    'status',
    'issued_at',
    'expires_at',
    'issued_by',
    'notes',
])]
class Accreditation extends Model
{
    /** @use HasFactory<\Database\Factories\AccreditationFactory> */
    use Auditable, BelongsToOrganization, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function accreditable(): MorphTo
    {
        return $this->morphTo();
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function resolveAuditOrganizationId(): ?int
    {
        return $this->organization_id;
    }
}
