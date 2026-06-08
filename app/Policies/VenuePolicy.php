<?php

namespace App\Policies;

use App\Enums\EventAssignmentRole;
use App\Models\Event;
use App\Models\Organization;
use App\Models\User;
use App\Models\Venue;
use App\Support\Permissions;

class VenuePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canViewVenues();
    }

    public function view(User $user, Venue $venue): bool
    {
        if ($user->isSystemOwner()) {
            return true;
        }

        if ($user->hasPermission(Permissions::slug('venues', 'view'), $venue->organization)) {
            return true;
        }

        return $user->assignedEvents()
            ->where('events.organization_id', $venue->organization_id)
            ->whereHas('venues', fn ($query) => $query->where('venues.id', $venue->id))
            ->exists();
    }

    public function create(User $user, ?Organization $organization = null): bool
    {
        if ($user->isSystemOwner()) {
            return true;
        }

        if ($organization !== null) {
            return $user->hasPermission(Permissions::slug('venues', 'create'), $organization);
        }

        return $user->hasPermissionInAnyOrganization(Permissions::slug('venues', 'create'));
    }

    public function update(User $user, Venue $venue): bool
    {
        if ($user->isSystemOwner()) {
            return true;
        }

        return $user->hasPermission(Permissions::slug('venues', 'update'), $venue->organization)
            || $user->hasPermission(Permissions::slug('venues', 'manage'), $venue->organization);
    }

    public function delete(User $user, Venue $venue): bool
    {
        return $user->hasPermission(Permissions::slug('venues', 'delete'), $venue->organization)
            || $user->hasPermission(Permissions::slug('venues', 'delete'));
    }

    public function attach(User $user, Event $event): bool
    {
        if ($user->isSystemOwner()) {
            return true;
        }

        if ($user->hasPermission(Permissions::slug('venues', 'manage'), $event->organization)) {
            return true;
        }

        return $user->isAssignedToEvent($event, [
            EventAssignmentRole::EventOrganizer->value,
            EventAssignmentRole::SportsManager->value,
        ]);
    }

    public function manageAtEvent(User $user, Event $event): bool
    {
        return $this->attach($user, $event);
    }
}