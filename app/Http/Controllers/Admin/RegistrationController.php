<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RegistrationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateRegistrationStatusRequest;
use App\Models\Event;
use App\Models\Registration;
use App\Services\RegistrationWorkflowService;
use Illuminate\Http\RedirectResponse;

class RegistrationController extends Controller
{
    public function updateStatus(
        UpdateRegistrationStatusRequest $request,
        Event $event,
        Registration $registration,
        RegistrationWorkflowService $workflowService,
    ): RedirectResponse {
        abort_unless($registration->event_id === $event->id, 404);

        $workflowService->transition(
            $registration,
            RegistrationStatus::from($request->validated('status')),
            $request->validated('rejected_reason'),
        );

        return back()->with('success', 'Registration status updated.');
    }
}