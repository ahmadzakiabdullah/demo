<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAccreditationRequest;
use App\Http\Requests\Admin\UpdateAccreditationRequest;
use App\Models\Accreditation;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Official;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccreditationController extends Controller
{
    public function index(Request $request, Event $event): Response
    {
        $this->authorize('viewAny', Accreditation::class);

        $accreditations = $event->accreditations()
            ->with(['accreditable'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($acc) => [
                'id' => $acc->id,
                'type' => $acc->type,
                'status' => $acc->status,
                'qr_code' => $acc->qr_code,
                'accreditable' => $acc->accreditable?->only(['id', 'name']),
                'event_participant' => null,
            ]);

        return Inertia::render('Admin/Events/Accreditations/Index', [
            'event' => $event->only(['id', 'name', 'slug']),
            'accreditations' => $accreditations,
            'participants' => $event->eventParticipants()->with('teams', 'athletes')->get(['id', 'name', 'type']),
        ]);
    }

    public function create(Event $event): Response
    {
        $this->authorize('create', Accreditation::class);

        return Inertia::render('Admin/Events/Accreditations/Create', [
            'event' => $event->only(['id', 'name', 'slug']),
            'participants' => $event->eventParticipants()->with(['teams:id,event_participant_id,name', 'athletes:id,event_participant_id,name'])->get(['id', 'name', 'type']),
            'types' => ['athlete', 'official', 'volunteer', 'media'],
        ]);
    }

    public function store(StoreAccreditationRequest $request, Event $event): RedirectResponse
    {
        $validated = $request->validated();

        $accreditation = Accreditation::create([
            'organization_id' => $event->organization_id,
            'event_id' => $event->id,
            'accreditable_type' => $validated['accreditable_type'],
            'accreditable_id' => $validated['accreditable_id'],
            'type' => $validated['type'],
            'qr_code' => 'QR-' . uniqid(), // will be updated with real QR
            'status' => 'active',
            'issued_at' => now(),
            'issued_by' => auth()->id(),
            'notes' => $validated['notes'] ?? null,
        ]);

        // Generate real QR data
        $accreditation->update(['qr_code' => $accreditation->generateQrData()]);

        return redirect()->route('admin.events.accreditations.show', [$event, $accreditation])
            ->with('success', 'Accreditation issued successfully.');
    }

    public function show(Event $event, Accreditation $accreditation): Response
    {
        $this->authorize('view', $accreditation);

        return Inertia::render('Admin/Events/Accreditations/Show', [
            'event' => $event->only(['id', 'name', 'slug']),
            'accreditation' => $accreditation->load(['accreditable', 'issuedBy']),
            'qrSvg' => $accreditation->getQrCodeSvg(),
        ]);
    }

    public function edit(Event $event, Accreditation $accreditation): Response
    {
        $this->authorize('update', $accreditation);

        return Inertia::render('Admin/Events/Accreditations/Edit', [
            'event' => $event->only(['id', 'name', 'slug']),
            'accreditation' => $accreditation,
            'types' => ['athlete', 'official', 'volunteer', 'media'],
        ]);
    }

    public function update(UpdateAccreditationRequest $request, Event $event, Accreditation $accreditation): RedirectResponse
    {
        $validated = $request->validated();

        $accreditation->update($validated);

        return redirect()->route('admin.events.accreditations.show', [$event, $accreditation])
            ->with('success', 'Accreditation updated.');
    }

    public function destroy(Event $event, Accreditation $accreditation): RedirectResponse
    {
        $this->authorize('delete', $accreditation);

        $accreditation->delete();

        return redirect()->route('admin.events.accreditations.index', $event)
            ->with('success', 'Accreditation revoked.');
    }
}
