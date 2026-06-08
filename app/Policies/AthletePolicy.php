<?php

namespace App\Policies;

use App\Enums\EventAssignmentRole;
use App\Models\Athlete;
use App\Models\Event;
use App\Models\User;
use App\Support\Permissions;

class AthletePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canViewAthletes();
    }

    public function view(User $user, Athlete $athlete): bool
    {
        if ($user->isSystemOwner()) {
            return true;
        }

        if ($athlete->isOwnedBy($user)) {
            return true;
        }

        if ($user->hasPermission(Permissions::slug('athletes', 'view'), $athlete->organization)) {
            return true;
        }

        return $user->organizations()
            ->where('organizations.id', $athlete->organization_id)
            ->exists()
            && $user->assignedEvents()
                ->whereHas('organization', fn ($query) => $query->where('organizations.id', $athlete->organization_id))
                ->exists();
    }

    public function create(User $user, Event $event): bool
    {
        if ($user->isSystemOwner()) {
            return true;
        }

        if ($user->hasPermission(Permissions::slug('athletes', 'create'), $event->organization)) {
            return true;
        }

        return $user->isAssignedToEvent($event, [
            EventAssignmentRole::TeamManager->value,
        ]);
    }

    public function update(User $user, Athlete $athlete): bool
    {
        if ($user->isSystemOwner()) {
            return true;
        }

        if ($athlete->isOwnedBy($user)) {
            return $user->hasPermission(Permissions::slug('athletes', 'update'), $athlete->organization)
                || $user->systemRoleHasPermission(Permissions::slug('athletes', 'update'));
        }

        return $user->hasPermission(Permissions::slug('athletes', 'update'), $athlete->organization);
    }

    public function delete(User $user, Athlete $athlete): bool
    {
        if ($athlete->isOwnedBy($user)) {
            return false;
        }

        return $user->hasPermission(Permissions::slug('athletes', 'delete'), $athlete->organization)
            || $user->hasPermission(Permissions::slug('athletes', 'delete'));
    }
}