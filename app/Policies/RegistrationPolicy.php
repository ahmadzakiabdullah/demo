<?php

namespace App\Policies;

use App\Enums\EventAssignmentRole;
use App\Models\Registration;
use App\Models\User;
use App\Support\Permissions;

class RegistrationPolicy
{
    public function updateStatus(User $user, Registration $registration): bool
    {
        if ($user->isSystemOwner()) {
            return true;
        }

        $organization = $registration->event->organization;

        if ($user->hasPermission(Permissions::slug('athletes', 'manage'), $organization)
            || $user->hasPermission(Permissions::slug('teams', 'manage'), $organization)
            || $user->hasPermission(Permissions::slug('officials', 'manage'), $organization)) {
            return true;
        }

        if ($user->isAssignedToEvent($registration->event, [
            EventAssignmentRole::EventOrganizer->value,
            EventAssignmentRole::SportsManager->value,
        ])) {
            return true;
        }

        return $user->hasPermission(Permissions::slug('athletes', 'update'), $organization)
            && $user->isAssignedToEvent($registration->event, [
                EventAssignmentRole::TeamManager->value,
            ]);
    }
}