<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\User;
use App\Support\Permissions;

class EventParticipantPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canViewEventParticipants();
    }

    public function view(User $user, EventParticipant $participant): bool
    {
        if ($user->isSystemOwner()) {
            return true;
        }

        if ($user->hasPermission(Permissions::slug('event_participants', 'view'), $participant->organization)) {
            return true;
        }

        return $user->isAssignedToEvent($participant->event);
    }

    public function create(User $user, Event $event): bool
    {
        if ($user->isSystemOwner()) {
            return true;
        }

        return $user->hasPermission(Permissions::slug('event_participants', 'create'), $event->organization)
            || $user->hasPermission(Permissions::slug('event_participants', 'manage'), $event->organization)
            || $user->isAssignedToEvent($event);
    }

    public function update(User $user, EventParticipant $participant): bool
    {
        if ($user->isSystemOwner()) {
            return true;
        }

        return $user->hasPermission(Permissions::slug('event_participants', 'update'), $participant->organization)
            || $user->hasPermission(Permissions::slug('event_participants', 'manage'), $participant->organization);
    }

    public function delete(User $user, EventParticipant $participant): bool
    {
        if ($user->isSystemOwner()) {
            return true;
        }

        return $user->hasPermission(Permissions::slug('event_participants', 'delete'), $participant->organization);
    }
}