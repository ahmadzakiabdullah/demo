<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'competition_id',
    'rankable_type',
    'rankable_id',
    'position',
    'points',
    'played',
    'won',
    'drawn',
    'lost',
    'scored_for',
    'scored_against',
])]
class Ranking extends Model
{
    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    public function rankable(): MorphTo
    {
        return $this->morphTo();
    }

    public function goalDifference(): int
    {
        return $this->scored_for - $this->scored_against;
    }
}