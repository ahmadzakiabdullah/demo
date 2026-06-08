<?php

namespace App\Policies;

use App\Enums\EventAssignmentRole;
use App\Models\MatchGame;
use App\Models\Result;
use App\Models\User;
use App\Support\Permissions;

class ResultPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canViewResults();
    }

    public function view(User $user, Result $result): bool
    {
        if ($user->isSystemOwner()) {
            return true;
        }

        $organization = $result->match?->competition()?->organization;

        if ($organization && $user->hasPermission(Permissions::slug('results', 'view'), $organization)) {
            return true;
        }

        return $user->isAssignedToEvent($result->match?->event());
    }

    public function enter(User $user, MatchGame $match): bool
    {
        if ($user->isSystemOwner()) {
            return true;
        }

        $competition = $match->competition();
        $organization = $competition?->organization;

        if ($organization && $user->hasPermission(Permissions::slug('results', 'manage'), $organization)) {
            return true;
        }

        if ($organization && $user->hasPermission(Permissions::slug('results', 'create'), $organization)) {
            return true;
        }

        if ($competition && $user->isAssignedToEvent($competition->event, [
            EventAssignmentRole::SportsManager->value,
            EventAssignmentRole::EventOrganizer->value,
        ])) {
            return true;
        }

        return $match->officials()
            ->whereHas('official', fn ($query) => $query->where('user_id', $user->id))
            ->exists();
    }

    public function confirm(User $user, Result $result): bool
    {
        if ($user->isSystemOwner()) {
            return true;
        }

        $organization = $result->match?->competition()?->organization;

        return $organization !== null && (
            $user->hasPermission(Permissions::slug('results', 'manage'), $organization)
            || $user->hasPermission(Permissions::slug('results', 'update'), $organization)
        );
    }
}