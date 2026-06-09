<?php

namespace App\Policies;

use App\Models\EventParticipant;
use App\Models\ParticipantSportEntry;
use App\Models\User;
use App\Support\Permissions;

class ParticipantSportEntryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canViewParticipantSportEntries();
    }

    public function view(User $user, ParticipantSportEntry $entry): bool
    {
        if ($user->isSystemOwner()) {
            return true;
        }

        $participant = $entry->eventParticipant;

        if ($user->hasPermission(Permissions::slug('participant_sport_entries', 'view'), $participant->organization)) {
            return true;
        }

        return $user->isAssignedToEvent($participant->event);
    }

    public function create(User $user, EventParticipant $participant): bool
    {
        if ($user->isSystemOwner()) {
            return true;
        }

        return $user->hasPermission(Permissions::slug('participant_sport_entries', 'create'), $participant->organization)
            || $user->hasPermission(Permissions::slug('participant_sport_entries', 'manage'), $participant->organization)
            || $user->isAssignedToEvent($participant->event);
    }

    public function update(User $user, ParticipantSportEntry $entry): bool
    {
        if ($user->isSystemOwner()) {
            return true;
        }

        return $user->hasPermission(Permissions::slug('participant_sport_entries', 'update'), $entry->eventParticipant->organization)
            || $user->hasPermission(Permissions::slug('participant_sport_entries', 'manage'), $entry->eventParticipant->organization);
    }

    public function delete(User $user, ParticipantSportEntry $entry): bool
    {
        if ($user->isSystemOwner()) {
            return true;
        }

        return $user->hasPermission(Permissions::slug('participant_sport_entries', 'delete'), $entry->eventParticipant->organization);
    }
}