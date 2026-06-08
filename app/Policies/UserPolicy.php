<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;
use App\Support\Permissions;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permissions::slug('users', 'view'))
            || $user->hasPermissionInAnyOrganization(Permissions::slug('users', 'view'));
    }

    public function view(User $user, User $model): bool
    {
        if ($user->hasPermission(Permissions::slug('users', 'view'))) {
            return true;
        }

        $organization = $this->currentOrganization();

        return $organization !== null
            && $user->hasPermission(Permissions::slug('users', 'view'), $organization)
            && $model->organizations()->where('organizations.id', $organization->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permissions::slug('users', 'create'))
            || $user->hasPermissionInAnyOrganization(Permissions::slug('users', 'create'));
    }

    public function update(User $user, User $model): bool
    {
        if ($user->hasPermission(Permissions::slug('users', 'update'))) {
            return true;
        }

        $organization = $this->currentOrganization();

        return $organization !== null
            && $user->hasPermission(Permissions::slug('users', 'update'), $organization)
            && $model->organizations()->where('organizations.id', $organization->id)->exists();
    }

    public function delete(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return false;
        }

        if ($user->hasPermission(Permissions::slug('users', 'delete'))) {
            return true;
        }

        $organization = $this->currentOrganization();

        return $organization !== null
            && $user->hasPermission(Permissions::slug('users', 'delete'), $organization)
            && $model->organizations()->where('organizations.id', $organization->id)->exists();
    }

    private function currentOrganization(): ?Organization
    {
        $organization = request()->attributes->get('currentOrganization');

        return $organization instanceof Organization ? $organization : null;
    }
}