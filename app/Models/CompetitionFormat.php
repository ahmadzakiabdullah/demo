<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'slug',
    'description',
    'sort_order',
])]
class CompetitionFormat extends Model
{
    public function competitions(): HasMany
    {
        return $this->hasMany(Competition::class);
    }

    public function supportsGroups(): bool
    {
        return in_array($this->slug, ['group_stage'], true);
    }

    public function supportsKnockoutPhase(): bool
    {
        return $this->slug === 'group_stage';
    }
}