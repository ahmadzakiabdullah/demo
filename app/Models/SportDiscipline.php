<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['sport_id', 'name', 'slug', 'sort_order'])]
class SportDiscipline extends Model
{
    public function sport(): BelongsTo
    {
        return $this->belongsTo(Sport::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(SportCategory::class)->orderBy('sort_order')->orderBy('name');
    }
}