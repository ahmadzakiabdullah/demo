<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Database\Factories\FixtureFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'competition_id',
    'group_id',
    'name',
    'round',
    'sort_order',
])]
class Fixture extends Model
{
    /** @use HasFactory<FixtureFactory> */
    use Auditable, HasFactory;

    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(CompetitionGroup::class, 'group_id');
    }

    public function matches(): HasMany
    {
        return $this->hasMany(MatchGame::class, 'fixture_id');
    }

    public function resolveAuditOrganizationId(): ?int
    {
        return $this->competition?->organization_id;
    }
}