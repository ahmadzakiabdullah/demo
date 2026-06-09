<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\RegistrationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreParticipantSportEntryRequest;
use App\Http\Resources\Api\V1\ParticipantSportEntryResource;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\ParticipantSportEntry;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class ParticipantSportEntryController extends Controller
{
    public function store(
        StoreParticipantSportEntryRequest $request,
        Event $event,
        EventParticipant $participant,
    ): JsonResponse {
        abort_unless($participant->event_id === $event->id, 404);

        $validated = $request->validated();
        $status = RegistrationStatus::from($validated['status']);

        $entry = ParticipantSportEntry::create([
            'event_participant_id' => $participant->id,
            'sport_id' => $validated['sport_id'],
            'sport_category_id' => $validated['sport_category_id'] ?? null,
            'sport_division_id' => $validated['sport_division_id'] ?? null,
            'status' => $status,
            'notes' => $validated['notes'] ?? null,
            'submitted_at' => in_array($status, [RegistrationStatus::Submitted, RegistrationStatus::Approved], true)
                ? now()
                : null,
            'approved_at' => $status === RegistrationStatus::Approved ? now() : null,
        ]);

        $entry->load(['sport:id,name,slug', 'sportCategory:id,name', 'sportDivision:id,name']);

        return ApiResponse::success(new ParticipantSportEntryResource($entry), 'Sport entry added.', 201);
    }

    public function destroy(
        Event $event,
        EventParticipant $participant,
        ParticipantSportEntry $entry,
    ): JsonResponse {
        $this->authorize('delete', $entry);
        abort_unless($participant->event_id === $event->id, 404);
        abort_unless($entry->event_participant_id === $participant->id, 404);

        $entry->delete();

        return ApiResponse::success(message: 'Sport entry removed.');
    }
}