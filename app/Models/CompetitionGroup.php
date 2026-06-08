<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'competition_id',
    'name',
    'slug',
    'sort_order',
])]
class CompetitionGroup extends Model
{
    protected $table = 'groups';

    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    public function fixtures(): HasMany
    {
        return $this->hasMany(Fixture::class, 'group_id');
    }
}