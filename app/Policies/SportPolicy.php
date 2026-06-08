<?php

namespace App\Policies;

use App\Enums\EventAssignmentRole;
use App\Models\Event;
use App\Models\Sport;
use App\Models\User;
use App\Support\Permissions;

class SportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canViewSports();
    }

    public function view(User $user, Sport $sport): bool
    {
        return $this->canAccessEventSports($user, $sport->event);
    }

    public function create(User $user, Event $event): bool
    {
        if ($user->isSystemOwner()) {
            return true;
        }

        if ($user->hasPermission(Permissions::slug('sports', 'create'), $event->organization)) {
            return true;
        }

        return $user->isAssignedToEvent($event, [
            EventAssignmentRole::SportsManager->value,
        ]);
    }

    public function update(User $user, Sport $sport): bool
    {
        if ($user->isSystemOwner()) {
            return true;
        }

        if ($user->hasPermission(Permissions::slug('sports', 'update'), $sport->event->organization)) {
            return true;
        }

        return $user->isAssignedToEvent($sport->event, [
            EventAssignmentRole::SportsManager->value,
            EventAssignmentRole::EventOrganizer->value,
        ]);
    }

    public function delete(User $user, Sport $sport): bool
    {
        return $user->hasPermission(Permissions::slug('sports', 'delete'), $sport->event->organization)
            || $user->hasPermission(Permissions::slug('sports', 'delete'));
    }

    private function canAccessEventSports(User $user, Event $event): bool
    {
        if ($user->isSystemOwner()) {
            return true;
        }

        if ($user->hasPermission(Permissions::slug('sports', 'view'), $event->organization)) {
            return true;
        }

        return $user->isAssignedToEvent($event);
    }
}