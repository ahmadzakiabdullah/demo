<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Database\Factories\MedalCeremonyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'organization_id',
    'event_id',
    'sport_id',
    'venue_id',
    'name',
    'scheduled_at',
    'duration_minutes',
    'notes',
])]
class MedalCeremony extends Model
{
    /** @use HasFactory<MedalCeremonyFactory> */
    use Auditable, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function sport(): BelongsTo
    {
        return $this->belongsTo(Sport::class);
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function resolveAuditOrganizationId(): ?int
    {
        return $this->organization_id;
    }
}