<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Database\Factories\VenueFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'organization_id',
    'name',
    'slug',
    'address',
    'capacity',
    'timezone',
    'notes',
])]
class Venue extends Model
{
    /** @use HasFactory<VenueFactory> */
    use Auditable, HasFactory, SoftDeletes;

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function facilities(): HasMany
    {
        return $this->hasMany(Facility::class)->orderBy('sort_order')->orderBy('name');
    }

    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class)
            ->withPivot(['is_primary', 'notes'])
            ->withTimestamps();
    }

    public function sports(): BelongsToMany
    {
        return $this->belongsToMany(Sport::class, 'event_sport_venue')
            ->withPivot(['event_id'])
            ->withTimestamps();
    }

    public function resolveAuditOrganizationId(): ?int
    {
        return $this->organization_id;
    }
}