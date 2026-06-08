<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;
use App\Support\Permissions;

class OrganizationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permissions::slug('organizations', 'view'));
    }

    public function view(User $user, Organization $organization): bool
    {
        return $user->hasPermission(Permissions::slug('organizations', 'view'), $organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permissions::slug('organizations', 'create'));
    }

    public function update(User $user, Organization $organization): bool
    {
        return $user->hasPermission(Permissions::slug('organizations', 'update'), $organization);
    }

    public function delete(User $user, Organization $organization): bool
    {
        return $user->hasPermission(Permissions::slug('organizations', 'delete'));
    }
}