<?php

namespace App\Models;

use App\Enums\MatchParticipantSide;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'match_id',
    'participant_type',
    'participant_id',
    'side',
    'sort_order',
])]
class MatchParticipant extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'side' => MatchParticipantSide::class,
        ];
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(MatchGame::class, 'match_id');
    }

    public function participant(): MorphTo
    {
        return $this->morphTo();
    }
}