<?php

namespace App\Models;

use App\Enums\SportStatus;
use App\Models\Concerns\Auditable;
use Database\Factories\SportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'event_id',
    'name',
    'slug',
    'template_slug',
    'status',
    'rules',
    'score_schema',
])]
class Sport extends Model
{
    /** @use HasFactory<SportFactory> */
    use Auditable, HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => SportStatus::class,
            'rules' => 'array',
            'score_schema' => 'array',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function disciplines(): HasMany
    {
        return $this->hasMany(SportDiscipline::class)->orderBy('sort_order')->orderBy('name');
    }

    public function venues(): BelongsToMany
    {
        return $this->belongsToMany(Venue::class, 'event_sport_venue')
            ->withPivot(['event_id'])
            ->withTimestamps();
    }

    public function resolveAuditOrganizationId(): ?int
    {
        return $this->event?->organization_id;
    }
}