<?php

namespace App\Models;

use App\Enums\SportGender;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'sport_discipline_id',
    'name',
    'slug',
    'gender',
    'min_age',
    'max_age',
    'min_weight',
    'max_weight',
    'sort_order',
])]
class SportCategory extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gender' => SportGender::class,
            'min_weight' => 'decimal:2',
            'max_weight' => 'decimal:2',
        ];
    }

    public function discipline(): BelongsTo
    {
        return $this->belongsTo(SportDiscipline::class, 'sport_discipline_id');
    }

    public function divisions(): HasMany
    {
        return $this->hasMany(SportDivision::class)->orderBy('sort_order')->orderBy('name');
    }
}