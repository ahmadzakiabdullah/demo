<?php

namespace App\Policies;

use App\Enums\EventAssignmentRole;
use App\Models\Event;
use App\Models\Official;
use App\Models\User;
use App\Support\Permissions;

class OfficialPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canViewOfficials();
    }

    public function view(User $user, Official $official): bool
    {
        if ($user->isSystemOwner()) {
            return true;
        }

        if ($official->isOwnedBy($user)) {
            return true;
        }

        if ($user->hasPermission(Permissions::slug('officials', 'view'), $official->organization)) {
            return true;
        }

        return $user->organizations()
            ->where('organizations.id', $official->organization_id)
            ->exists()
            && $user->assignedEvents()
                ->whereHas('organization', fn ($query) => $query->where('organizations.id', $official->organization_id))
                ->exists();
    }

    public function create(User $user, Event $event): bool
    {
        if ($user->isSystemOwner()) {
            return true;
        }

        if ($user->hasPermission(Permissions::slug('officials', 'create'), $event->organization)) {
            return true;
        }

        return $user->isAssignedToEvent($event, [
            EventAssignmentRole::TeamManager->value,
        ]);
    }

    public function update(User $user, Official $official): bool
    {
        if ($user->isSystemOwner()) {
            return true;
        }

        if ($official->isOwnedBy($user)) {
            return $user->hasPermission(Permissions::slug('officials', 'update'), $official->organization)
                || $user->systemRoleHasPermission(Permissions::slug('officials', 'update'));
        }

        return $user->hasPermission(Permissions::slug('officials', 'update'), $official->organization);
    }

    public function delete(User $user, Official $official): bool
    {
        if ($official->isOwnedBy($user)) {
            return false;
        }

        return $user->hasPermission(Permissions::slug('officials', 'delete'), $official->organization)
            || $user->hasPermission(Permissions::slug('officials', 'delete'));
    }
}