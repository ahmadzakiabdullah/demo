<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['sport_category_id', 'name', 'slug', 'sort_order'])]
class SportDivision extends Model
{
    public function category(): BelongsTo
    {
        return $this->belongsTo(SportCategory::class, 'sport_category_id');
    }
}