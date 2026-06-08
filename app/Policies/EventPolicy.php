<?php

namespace App\Policies;

use App\Enums\EventAssignmentRole;
use App\Models\Event;
use App\Models\User;
use App\Support\Permissions;

class EventPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canViewEvents();
    }

    public function view(User $user, Event $event): bool
    {
        if ($user->isSystemOwner()) {
            return true;
        }

        if ($user->hasPermission(Permissions::slug('events', 'view'), $event->organization)) {
            return true;
        }

        return $user->isAssignedToEvent($event);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permissions::slug('events', 'create'))
            || $user->hasPermissionInAnyOrganization(Permissions::slug('events', 'create'));
    }

    public function update(User $user, Event $event): bool
    {
        if ($user->isSystemOwner()) {
            return true;
        }

        if ($user->hasPermission(Permissions::slug('events', 'update'), $event->organization)) {
            return true;
        }

        return $user->isAssignedToEvent($event, [
            EventAssignmentRole::EventOrganizer->value,
            EventAssignmentRole::SportsManager->value,
        ]);
    }

    public function delete(User $user, Event $event): bool
    {
        return $user->hasPermission(Permissions::slug('events', 'delete'), $event->organization)
            || $user->hasPermission(Permissions::slug('events', 'delete'));
    }

    public function manageAssignments(User $user, Event $event): bool
    {
        return $this->update($user, $event);
    }
}