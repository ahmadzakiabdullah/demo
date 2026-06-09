<?php

namespace App\Models;

use App\Enums\EventParticipantStatus;
use App\Enums\EventParticipantType;
use App\Models\Concerns\Auditable;
use App\Scopes\OrganizationScope;
use Database\Factories\EventParticipantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'organization_id',
    'event_id',
    'branch_id',
    'type',
    'name',
    'code',
    'status',
    'metadata',
])]
class EventParticipant extends Model
{
    /** @use HasFactory<EventParticipantFactory> */
    use Auditable, HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => EventParticipantType::class,
            'status' => EventParticipantStatus::class,
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new OrganizationScope);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function sportEntries(): HasMany
    {
        return $this->hasMany(ParticipantSportEntry::class);
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    public function athletes(): HasMany
    {
        return $this->hasMany(Athlete::class);
    }

    public function resolveAuditOrganizationId(): ?int
    {
        return $this->organization_id;
    }
}