<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\TeamFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'organization_id',
    'event_participant_id',
    'event_id',
    'sport_id',
    'name',
    'slug',
    'coach_user_id',
    'manager_user_id',
    'notes',
])]
class Team extends Model
{
    /** @use HasFactory<TeamFactory> */
    use Auditable, BelongsToOrganization, HasFactory, SoftDeletes;

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function eventParticipant(): BelongsTo
    {
        return $this->belongsTo(EventParticipant::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function sport(): BelongsTo
    {
        return $this->belongsTo(Sport::class);
    }

    public function coach(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coach_user_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_user_id');
    }

    public function athletes(): BelongsToMany
    {
        return $this->belongsToMany(Athlete::class, 'team_athlete')
            ->withPivot(['role', 'jersey_number'])
            ->withTimestamps()
            ->orderBy('athletes.name');
    }

    public function registrations(): MorphMany
    {
        return $this->morphMany(Registration::class, 'registrable');
    }

    public function isManagedBy(User $user): bool
    {
        return $this->coach_user_id === $user->id
            || $this->manager_user_id === $user->id;
    }

    public function resolveAuditOrganizationId(): ?int
    {
        return $this->organization_id;
    }
}