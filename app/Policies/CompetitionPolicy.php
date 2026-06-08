<?php

namespace App\Policies;

use App\Enums\EventAssignmentRole;
use App\Models\Competition;
use App\Models\Event;
use App\Models\User;
use App\Support\Permissions;

class CompetitionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canViewCompetitions();
    }

    public function view(User $user, Competition $competition): bool
    {
        if ($user->isSystemOwner()) {
            return true;
        }

        if ($user->hasPermission(Permissions::slug('competitions', 'view'), $competition->organization)) {
            return true;
        }

        return $user->isAssignedToEvent($competition->event);
    }

    public function create(User $user, Event $event): bool
    {
        if ($user->isSystemOwner()) {
            return true;
        }

        if ($user->hasPermission(Permissions::slug('competitions', 'create'), $event->organization)) {
            return true;
        }

        return $user->isAssignedToEvent($event, [
            EventAssignmentRole::EventOrganizer->value,
            EventAssignmentRole::SportsManager->value,
        ]);
    }

    public function update(User $user, Competition $competition): bool
    {
        if ($user->isSystemOwner()) {
            return true;
        }

        return $user->hasPermission(Permissions::slug('competitions', 'update'), $competition->organization)
            || $user->hasPermission(Permissions::slug('competitions', 'manage'), $competition->organization);
    }

    public function delete(User $user, Competition $competition): bool
    {
        return $user->hasPermission(Permissions::slug('competitions', 'delete'), $competition->organization)
            || $user->hasPermission(Permissions::slug('competitions', 'delete'));
    }

    public function manageSchedule(User $user, Competition $competition): bool
    {
        if ($user->isSystemOwner()) {
            return true;
        }

        if ($user->hasPermission(Permissions::slug('competitions', 'manage'), $competition->organization)) {
            return true;
        }

        return $user->isAssignedToEvent($competition->event, [
            EventAssignmentRole::EventOrganizer->value,
            EventAssignmentRole::SportsManager->value,
        ]);
    }
}