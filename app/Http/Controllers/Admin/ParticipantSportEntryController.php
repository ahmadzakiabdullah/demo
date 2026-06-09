<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RegistrationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreParticipantSportEntryRequest;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\ParticipantSportEntry;
use Illuminate\Http\RedirectResponse;

class ParticipantSportEntryController extends Controller
{
    public function store(StoreParticipantSportEntryRequest $request, Event $event, EventParticipant $participant): RedirectResponse
    {
        abort_unless($participant->event_id === $event->id, 404);

        $validated = $request->validated();
        $status = RegistrationStatus::from($validated['status']);

        ParticipantSportEntry::create([
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

        return redirect()->route('admin.events.participants.show', [$event, $participant])
            ->with('success', 'Sport entry added successfully.');
    }

    public function destroy(Event $event, EventParticipant $participant, ParticipantSportEntry $entry): RedirectResponse
    {
        $this->authorize('delete', $entry);
        abort_unless($participant->event_id === $event->id, 404);
        abort_unless($entry->event_participant_id === $participant->id, 404);

        $entry->delete();

        return redirect()->route('admin.events.participants.show', [$event, $participant])
            ->with('success', 'Sport entry removed successfully.');
    }
}