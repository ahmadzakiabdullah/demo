<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    /**
     * @var list<string>
     */
    private const SENSITIVE_ATTRIBUTES = [
        'password',
        'remember_token',
    ];

    public static function log(
        string $action,
        Model $model,
        ?array $oldValues = null,
        ?array $newValues = null,
    ): void {
        if ($model instanceof AuditLog) {
            return;
        }

        AuditLog::query()->create([
            'organization_id' => static::resolveOrganizationId($model),
            'user_id' => Auth::id(),
            'action' => $action,
            'auditable_type' => $model->getMorphClass(),
            'auditable_id' => $model->getKey(),
            'old_values' => static::filterAttributes($oldValues),
            'new_values' => static::filterAttributes($newValues),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    private static function resolveOrganizationId(Model $model): ?int
    {
        if (method_exists($model, 'resolveAuditOrganizationId')) {
            return $model->resolveAuditOrganizationId();
        }

        if (isset($model->organization_id)) {
            return (int) $model->organization_id;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>|null  $attributes
     * @return array<string, mixed>|null
     */
    private static function filterAttributes(?array $attributes): ?array
    {
        if ($attributes === null) {
            return null;
        }

        return collect($attributes)
            ->except(self::SENSITIVE_ATTRIBUTES)
            ->all();
    }
}