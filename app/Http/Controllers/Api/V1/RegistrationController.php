<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\RegistrationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateRegistrationStatusRequest;
use App\Http\Resources\Api\V1\RegistrationResource;
use App\Models\Event;
use App\Models\Registration;
use App\Services\RegistrationWorkflowService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class RegistrationController extends Controller
{
    public function updateStatus(
        UpdateRegistrationStatusRequest $request,
        Event $event,
        Registration $registration,
        RegistrationWorkflowService $workflowService,
    ): JsonResponse {
        abort_unless($registration->event_id === $event->id, 404);

        $registration = $workflowService->transition(
            $registration,
            RegistrationStatus::from($request->validated('status')),
            $request->validated('rejected_reason'),
        );

        $registration->load(['sport', 'sportCategory', 'sportDivision']);

        return ApiResponse::success(new RegistrationResource($registration), 'Registration status updated.');
    }
}