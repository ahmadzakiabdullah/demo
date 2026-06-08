<?php

namespace App\Services;

use App\Enums\RegistrationStatus;
use App\Models\Athlete;
use App\Models\Official;
use App\Models\Registration;
use App\Models\Team;
use Illuminate\Validation\ValidationException;

class RegistrationWorkflowService
{
    public function __construct(
        private readonly EligibilityService $eligibilityService,
    ) {}

    public function transition(
        Registration $registration,
        RegistrationStatus $status,
        ?string $rejectedReason = null,
    ): Registration {
        if (! $registration->status->canTransitionTo($status)) {
            throw ValidationException::withMessages([
                'status' => "Cannot transition from {$registration->status->value} to {$status->value}.",
            ]);
        }

        $registration->loadMissing(['registrable', 'sportCategory', 'event']);

        if (in_array($status, [RegistrationStatus::Submitted, RegistrationStatus::Verified, RegistrationStatus::Approved], true)) {
            $registrable = $registration->registrable;

            if ($registrable instanceof Athlete) {
                $issues = $this->eligibilityService->issues(
                    $registrable,
                    $registration->sportCategory,
                    $registration->event,
                );

                if ($issues !== []) {
                    throw ValidationException::withMessages([
                        'eligibility' => $issues,
                    ]);
                }
            }

            if ($registrable instanceof Team) {
                $registrable->loadCount('athletes');

                if ($registrable->athletes_count < 1) {
                    throw ValidationException::withMessages([
                        'roster' => ['Team must have at least one athlete on the roster.'],
                    ]);
                }
            }

            if ($registrable instanceof Official) {
                $issues = $this->eligibilityService->officialIssues($registrable);

                if ($issues !== []) {
                    throw ValidationException::withMessages([
                        'eligibility' => $issues,
                    ]);
                }
            }
        }

        $attributes = ['status' => $status];

        if ($status === RegistrationStatus::Submitted) {
            $attributes['submitted_at'] = now();
        }

        if ($status === RegistrationStatus::Verified) {
            $attributes['verified_at'] = now();
        }

        if ($status === RegistrationStatus::Approved) {
            $attributes['approved_at'] = now();
        }

        if ($status === RegistrationStatus::Rejected) {
            $attributes['rejected_reason'] = $rejectedReason;
        }

        $registration->update($attributes);

        return $registration->fresh();
    }
}