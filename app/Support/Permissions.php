<?php

namespace App\Support;

class Permissions
{
    public const MODULES = [
        'organizations',
        'branches',
        'users',
        'roles',
        'events',
        'sports',
        'audit_logs',
    ];

    public const ACTIONS = [
        'view',
        'create',
        'update',
        'delete',
        'manage',
    ];

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        $permissions = [];

        foreach (self::MODULES as $module) {
            foreach (self::ACTIONS as $action) {
                $permissions[] = self::slug($module, $action);
            }
        }

        return $permissions;
    }

    public static function slug(string $module, string $action): string
    {
        return "{$module}.{$action}";
    }
}