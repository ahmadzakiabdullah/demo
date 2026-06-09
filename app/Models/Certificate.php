<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Certificate extends Model
{
    protected $fillable = [
        'organization_id', 'event_id', 'certifiable_type', 'certifiable_id',
        'type', 'file_path', 'issued_at', 'issued_by', 'notes'
    ];

    public function certifiable()
    {
        return $this->morphTo();
    }
}