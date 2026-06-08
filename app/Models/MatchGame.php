<?php

namespace App\Models;

use App\Enums\MatchStatus;
use App\Models\Concerns\Auditable;
use Database\Factories\MatchGameFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

#[Fillable([
    'fixture_id',
    'venue_id',
    'facility_id',
    'scheduled_at',
    'duration_minutes',
    'status',
    'notes',
    'winner_advances_to_match_id',
    'winner_advances_side',
    'loser_advances_to_match_id',
    'loser_advances_side',
    'bracket_lane',
])]
class MatchGame extends Model
{
    /** @use HasFactory<MatchGameFactory> */
    use Auditable, HasFactory;

    protected $table = 'matches';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'status' => MatchStatus::class,
        ];
    }

    public function fixture(): BelongsTo
    {
        return $this->belongsTo(Fixture::class);
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(MatchParticipant::class, 'match_id');
    }

    public function officials(): HasMany
    {
        return $this->hasMany(MatchOfficial::class, 'match_id');
    }

    public function result(): HasOne
    {
        return $this->hasOne(Result::class, 'match_id');
    }

    public function advancesTo(): BelongsTo
    {
        return $this->belongsTo(self::class, 'winner_advances_to_match_id');
    }

    public function loserAdvancesTo(): BelongsTo
    {
        return $this->belongsTo(self::class, 'loser_advances_to_match_id');
    }

    public function endsAt(): ?Carbon
    {
        if ($this->scheduled_at === null) {
            return null;
        }

        return $this->scheduled_at->copy()->addMinutes($this->duration_minutes);
    }

    public function competition(): ?Competition
    {
        return $this->fixture?->competition;
    }

    public function event(): ?Event
    {
        return $this->competition()?->event;
    }

    public function resolveAuditOrganizationId(): ?int
    {
        return $this->competition()?->organization_id;
    }
}