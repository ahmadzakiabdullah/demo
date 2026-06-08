<?php

namespace App\Models;

use App\Enums\MatchOfficialRole;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'match_id',
    'official_id',
    'role',
])]
class MatchOfficial extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => MatchOfficialRole::class,
        ];
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(MatchGame::class, 'match_id');
    }

    public function official(): BelongsTo
    {
        return $this->belongsTo(Official::class);
    }
}