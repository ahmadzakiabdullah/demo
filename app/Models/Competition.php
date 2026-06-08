<?php

namespace App\Models;

use App\Enums\CompetitionStatus;
use App\Models\Concerns\Auditable;
use Database\Factories\CompetitionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'organization_id',
    'event_id',
    'sport_id',
    'competition_format_id',
    'name',
    'slug',
    'status',
    'notes',
    'settings',
])]
class Competition extends Model
{
    /** @use HasFactory<CompetitionFactory> */
    use Auditable, HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => CompetitionStatus::class,
            'settings' => 'array',
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

    public function format(): BelongsTo
    {
        return $this->belongsTo(CompetitionFormat::class, 'competition_format_id');
    }

    public function groups(): HasMany
    {
        return $this->hasMany(CompetitionGroup::class)->orderBy('sort_order')->orderBy('name');
    }

    public function fixtures(): HasMany
    {
        return $this->hasMany(Fixture::class)->orderBy('sort_order')->orderBy('name');
    }

    public function matches(): HasManyThrough
    {
        return $this->hasManyThrough(MatchGame::class, Fixture::class);
    }

    public function rankings(): HasMany
    {
        return $this->hasMany(Ranking::class)->orderBy('position');
    }

    public function medals(): HasMany
    {
        return $this->hasMany(Medal::class);
    }

    public function competitionParticipants(): HasMany
    {
        return $this->hasMany(CompetitionParticipant::class);
    }

    public function resolveAuditOrganizationId(): ?int
    {
        return $this->organization_id;
    }
}