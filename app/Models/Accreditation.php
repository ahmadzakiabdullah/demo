<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class Accreditation extends Model
{
    protected $fillable = [
        'organization_id', 'event_id', 'accreditable_type', 'accreditable_id', 
        'type', 'qr_code', 'status', 'issued_at', 'expires_at', 'issued_by', 'notes'
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            $model->qr_code = $model->qr_code ?? (string) Str::uuid();
        });
    }

    public function accreditable()
    {
        return $this->morphTo();
    }

    public function generateQrData()
    {
        return 'QR-' . strtoupper(Str::random(12));
    }

    public function getQrCodeSvg()
    {
        return (string) QrCode::format('svg')->size(200)->generate($this->qr_code);
    }

    public function issuedBy()
    {
        return $this->belongsTo(User::class, 'issued_by');
    }
}