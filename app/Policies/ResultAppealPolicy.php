<?php

namespace App\Policies;

use App\Enums\EventAssignmentRole;
use App\Enums\ResultStatus;
use App\Models\Athlete;
use App\Models\Result;
use App\Models\ResultAppeal;
use App\Models\Team;
use App\Models\User;
use App\Support\Permissions;

class ResultAppealPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canViewResults();
    }

    public function view(User $user, ResultAppeal $appeal): bool
    {
        return app(ResultPolicy::class)->view($user, $appeal->result);
    }

    public function create(User $user, Result $result): bool
    {
        if ($user->isSystemOwner()) {
            return true;
        }

        if (! in_array($result->status, [ResultStatus::Confirmed, ResultStatus::Published], true)) {
            return false;
        }

        if ($result->openAppeal() !== null) {
            return false;
        }

        $organization = $result->match?->competition()?->organization;
        $event = $result->match?->event();

        if ($organization && $user->hasPermission(Permissions::slug('results', 'manage'), $organization)) {
            return true;
        }

        if ($event && $user->isAssignedToEvent($event, [
            EventAssignmentRole::EventOrganizer->value,
            EventAssignmentRole::SportsManager->value,
        ])) {
            return true;
        }

        return $this->isMatchParticipantRepresentative($user, $result);
    }

    public function resolve(User $user, ResultAppeal $appeal): bool
    {
        if (! $appeal->status->isOpen()) {
            return false;
        }

        if ($user->isSystemOwner()) {
            return true;
        }

        $organization = $appeal->result?->match?->competition()?->organization;
        $event = $appeal->result?->match?->event();

        if ($organization && (
            $user->hasPermission(Permissions::slug('results', 'manage'), $organization)
            || $user->hasPermission(Permissions::slug('results', 'update'), $organization)
        )) {
            return true;
        }

        return $event !== null && $user->isAssignedToEvent($event, [
            EventAssignmentRole::EventOrganizer->value,
            EventAssignmentRole::SportsManager->value,
        ]);
    }

    private function isMatchParticipantRepresentative(User $user, Result $result): bool
    {
        $match = $result->match;

        if ($match === null) {
            return false;
        }

        $match->loadMissing('participants.participant');

        foreach ($match->participants as $participant) {
            if ($participant->participant_type === Team::class) {
                $team = $participant->participant;

                if ($team instanceof Team && $team->isManagedBy($user)) {
                    return true;
                }
            }

            if ($participant->participant_type === Athlete::class) {
                $athlete = $participant->participant;

                if ($athlete instanceof Athlete && $athlete->user_id === $user->id) {
                    return true;
                }
            }
        }

        return false;
    }
}