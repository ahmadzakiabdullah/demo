<?php

namespace App\Models;

use App\Enums\SportGender;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\AthleteFactory;
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
    'user_id',
    'name',
    'dob',
    'gender',
    'nationality',
    'id_number',
    'photo_path',
    'medical_clearance',
    'weight',
])]
class Athlete extends Model
{
    /** @use HasFactory<AthleteFactory> */
    use Auditable, BelongsToOrganization, HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'dob' => 'date',
            'gender' => SportGender::class,
            'medical_clearance' => 'boolean',
            'weight' => 'decimal:2',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function eventParticipant(): BelongsTo
    {
        return $this->belongsTo(EventParticipant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function registrations(): MorphMany
    {
        return $this->morphMany(Registration::class, 'registrable');
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_athlete')
            ->withPivot(['role', 'jersey_number'])
            ->withTimestamps();
    }

    public function ageAt(?\DateTimeInterface $reference = null): ?int
    {
        if ($this->dob === null) {
            return null;
        }

        return $this->dob->diffInYears($reference ?? now());
    }

    public function isOwnedBy(User $user): bool
    {
        return $this->user_id !== null && $this->user_id === $user->id;
    }

    public function resolveAuditOrganizationId(): ?int
    {
        return $this->organization_id;
    }
}