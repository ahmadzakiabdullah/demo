<?php

namespace App\Models;

use App\Enums\EventStatus;
use App\Models\Concerns\Auditable;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'organization_id',
    'event_type_id',
    'event_category_id',
    'name',
    'slug',
    'status',
    'location',
    'description',
    'starts_at',
    'ends_at',
])]
class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use Auditable, HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => EventStatus::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function eventType(): BelongsTo
    {
        return $this->belongsTo(EventType::class);
    }

    public function eventCategory(): BelongsTo
    {
        return $this->belongsTo(EventCategory::class);
    }

    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['role'])
            ->withTimestamps();
    }

    public function sports(): HasMany
    {
        return $this->hasMany(Sport::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    public function venues(): BelongsToMany
    {
        return $this->belongsToMany(Venue::class)
            ->withPivot(['is_primary', 'notes'])
            ->withTimestamps();
    }

    public function competitions(): HasMany
    {
        return $this->hasMany(Competition::class);
    }

    public function medals(): HasMany
    {
        return $this->hasMany(Medal::class);
    }

    public function resolveAuditOrganizationId(): ?int
    {
        return $this->organization_id;
    }

    public function isPubliclyVisible(): bool
    {
        return in_array($this->status, [EventStatus::Published, EventStatus::Active], true);
    }
}