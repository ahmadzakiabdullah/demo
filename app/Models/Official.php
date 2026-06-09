<?php

namespace App\Models;

use App\Enums\OfficialType;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\OfficialFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'organization_id',
    'user_id',
    'name',
    'email',
    'type',
    'certification_level',
    'certification_expires_at',
])]
class Official extends Model
{
    /** @use HasFactory<OfficialFactory> */
    use Auditable, BelongsToOrganization, HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => OfficialType::class,
            'certification_expires_at' => 'date',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function registrations(): MorphMany
    {
        return $this->morphMany(Registration::class, 'registrable');
    }

    public function isOwnedBy(User $user): bool
    {
        return $this->user_id !== null && $this->user_id === $user->id;
    }

    public function resolveAuditOrganizationId(): ?int
    {
        return $this->organization_id;
    }
}