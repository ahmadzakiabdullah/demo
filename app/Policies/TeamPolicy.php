<?php

namespace App\Policies;

use App\Enums\EventAssignmentRole;
use App\Models\Event;
use App\Models\Team;
use App\Models\User;
use App\Support\Permissions;

class TeamPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canViewTeams();
    }

    public function view(User $user, Team $team): bool
    {
        if ($user->isSystemOwner()) {
            return true;
        }

        if ($team->isManagedBy($user)) {
            return true;
        }

        if ($user->hasPermission(Permissions::slug('teams', 'view'), $team->organization)) {
            return true;
        }

        return $user->isAssignedToEvent($team->event);
    }

    public function create(User $user, Event $event): bool
    {
        if ($user->isSystemOwner()) {
            return true;
        }

        if ($user->hasPermission(Permissions::slug('teams', 'create'), $event->organization)) {
            return true;
        }

        return $user->isAssignedToEvent($event, [
            EventAssignmentRole::TeamManager->value,
        ]);
    }

    public function update(User $user, Team $team): bool
    {
        if ($user->isSystemOwner()) {
            return true;
        }

        if ($team->isManagedBy($user)) {
            return $user->hasPermission(Permissions::slug('teams', 'update'), $team->organization)
                || $user->systemRoleHasPermission(Permissions::slug('teams', 'update'));
        }

        return $user->hasPermission(Permissions::slug('teams', 'update'), $team->organization);
    }

    public function delete(User $user, Team $team): bool
    {
        if ($team->isManagedBy($user)) {
            return false;
        }

        return $user->hasPermission(Permissions::slug('teams', 'delete'), $team->organization)
            || $user->hasPermission(Permissions::slug('teams', 'delete'));
    }

    public function manageRoster(User $user, Team $team): bool
    {
        if ($user->isSystemOwner()) {
            return true;
        }

        if ($team->isManagedBy($user)) {
            return $user->hasPermission(Permissions::slug('teams', 'manage'), $team->organization)
                || $user->hasPermission(Permissions::slug('teams', 'update'), $team->organization)
                || $user->systemRoleHasPermission(Permissions::slug('teams', 'manage'));
        }

        return $user->hasPermission(Permissions::slug('teams', 'manage'), $team->organization);
    }
}