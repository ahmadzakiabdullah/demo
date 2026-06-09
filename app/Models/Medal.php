<?php

namespace App\Models;

use App\Enums\MedalType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'event_id',
    'sport_id',
    'competition_id',
    'event_participant_id',
    'medalable_type',
    'medalable_id',
    'type',
])]
class Medal extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => MedalType::class,
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function sport(): BelongsTo
    {
        return $this->belongsTo(Sport::class);
    }

    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    public function eventParticipant(): BelongsTo
    {
        return $this->belongsTo(EventParticipant::class);
    }

    public function medalable(): MorphTo
    {
        return $this->morphTo();
    }

    public function resolveAuditOrganizationId(): ?int
    {
        return $this->eventParticipant?->organization_id ?? $this->event?->organization_id;
    }
}