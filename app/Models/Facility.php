<?php

namespace App\Models;

use App\Enums\FacilityType;
use App\Models\Concerns\Auditable;
use Database\Factories\FacilityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'venue_id',
    'name',
    'slug',
    'type',
    'capacity',
    'sort_order',
])]
class Facility extends Model
{
    /** @use HasFactory<FacilityFactory> */
    use Auditable, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => FacilityType::class,
        ];
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function resolveAuditOrganizationId(): ?int
    {
        return $this->venue?->organization_id;
    }
}