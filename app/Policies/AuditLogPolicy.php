<?php

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\User;
use App\Support\Permissions;

class AuditLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canViewAuditLogs();
    }

    public function view(User $user, AuditLog $auditLog): bool
    {
        if (! $user->hasPermission(Permissions::slug('audit_logs', 'view'))) {
            return false;
        }

        if ($user->isSystemOwner()) {
            return true;
        }

        if ($auditLog->organization_id === null) {
            return false;
        }

        return $user->organizations()
            ->where('organizations.id', $auditLog->organization_id)
            ->exists();
    }

    public function viewForOrganization(User $user, Organization $organization): bool
    {
        if ($user->isSystemOwner()) {
            return true;
        }

        return $user->hasPermission(Permissions::slug('audit_logs', 'view'), $organization);
    }
}