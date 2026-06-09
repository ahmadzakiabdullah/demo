<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Enums\OrganizationStatus;
use App\Enums\OrganizationType;
use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'name',
    'slug',
    'type',
    'logo_path',
    'timezone',
    'locale',
    'status',
    'is_tenant',
    'settings',
])]
class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
    use Auditable, HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => OrganizationType::class,
            'status' => OrganizationStatus::class,
            'is_tenant' => 'boolean',
            'settings' => 'array',
        ];
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function eventSeries(): HasMany
    {
        return $this->hasMany(EventSeries::class);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Organization>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Organization>
     */
    public function scopeSwitchable($query)
    {
        return $query->where('is_tenant', true);
    }

    public function venues(): HasMany
    {
        return $this->hasMany(Venue::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['role_id', 'status'])
            ->withTimestamps();
    }

    public function resolveAuditOrganizationId(): ?int
    {
        return $this->id;
    }
}