<?php

namespace App\Models\Concerns;

use App\Support\AuditLogger;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function (self $model): void {
            AuditLogger::log('created', $model, null, $model->getAttributes());
        });

        static::updated(function (self $model): void {
            $changes = $model->getChanges();

            if ($changes === []) {
                return;
            }

            $oldValues = collect($model->getOriginal())
                ->only(array_keys($changes))
                ->all();

            AuditLogger::log('updated', $model, $oldValues, $changes);
        });

        static::deleted(function (self $model): void {
            AuditLogger::log('deleted', $model, $model->getOriginal());
        });
    }
}