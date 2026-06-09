<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EventParticipantStatus;
use App\Enums\EventParticipantType;
use App\Enums\ParticipantUnitLabel;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImportEventParticipantsRequest;
use App\Http\Requests\Admin\StoreEventParticipantRequest;
use App\Http\Requests\Admin\UpdateEventParticipantRequest;
use App\Models\Branch;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Services\EventParticipantCsvImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EventParticipantController extends Controller
{
    public function index(Request $request, Event $event): Response
    {
        $this->authorize('view', $event);
        $this->authorize('viewAny', EventParticipant::class);

        $participants = $event->eventParticipants()
            ->withCount('sportEntries')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();
                $query->where(function ($builder) use ($search) {
                    $builder
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->string('type')))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (EventParticipant $participant) => $this->listPayload($participant));

        return Inertia::render('Admin/Events/Participants/Index', [
            'event' => $this->eventContext($event),
            'participants' => $participants,
            'participantTypes' => EventParticipantType::values(),
            'participantUnitLabel' => $event->participant_unit_label?->label() ?? 'Participant',
            'filters' => [
                'search' => $request->string('search')->toString(),
                'type' => $request->string('type')->toString(),
            ],
        ]);
    }

    public function create(Event $event): Response
    {
        $this->authorize('create', [EventParticipant::class, $event]);

        return Inertia::render('Admin/Events/Participants/Create', [
            'event' => $this->eventContext($event),
            'participantTypes' => EventParticipantType::values(),
            'statuses' => EventParticipantStatus::values(),
            'branches' => $this->branchOptions($event),
            'defaultType' => $this->defaultParticipantType($event),
        ]);
    }

    public function store(StoreEventParticipantRequest $request, Event $event): RedirectResponse
    {
        $participant = EventParticipant::create([
            ...$request->validated(),
            'organization_id' => $event->organization_id,
            'event_id' => $event->id,
        ]);

        return redirect()->route('admin.events.participants.show', [$event, $participant])
            ->with('success', 'Participant registered successfully.');
    }

    public function show(Event $event, EventParticipant $participant): Response
    {
        $this->authorize('view', $participant);
        abort_unless($participant->event_id === $event->id, 404);

        $participant->load([
            'sportEntries.sport:id,name,slug',
            'sportEntries.sportCategory:id,name',
            'sportEntries.sportDivision:id,name',
        ])->loadCount(['teams', 'athletes']);

        return Inertia::render('Admin/Events/Participants/Show', [
            'event' => $this->eventContext($event),
            'participant' => $this->detailPayload($participant),
            'sports' => $event->sports()->orderBy('name')->get(['id', 'name', 'slug']),
            'entryStatuses' => \App\Enums\RegistrationStatus::values(),
        ]);
    }

    public function edit(Event $event, EventParticipant $participant): Response
    {
        $this->authorize('update', $participant);
        abort_unless($participant->event_id === $event->id, 404);

        return Inertia::render('Admin/Events/Participants/Edit', [
            'event' => $this->eventContext($event),
            'participant' => [
                'id' => $participant->id,
                'type' => $participant->type->value,
                'name' => $participant->name,
                'code' => $participant->code,
                'branch_id' => $participant->branch_id,
                'status' => $participant->status->value,
            ],
            'participantTypes' => EventParticipantType::values(),
            'statuses' => EventParticipantStatus::values(),
            'branches' => $this->branchOptions($event),
        ]);
    }

    public function update(UpdateEventParticipantRequest $request, Event $event, EventParticipant $participant): RedirectResponse
    {
        abort_unless($participant->event_id === $event->id, 404);

        $participant->update($request->validated());

        return redirect()->route('admin.events.participants.show', [$event, $participant])
            ->with('success', 'Participant updated successfully.');
    }

    public function destroy(Event $event, EventParticipant $participant): RedirectResponse
    {
        $this->authorize('delete', $participant);
        abort_unless($participant->event_id === $event->id, 404);

        $participant->delete();

        return redirect()->route('admin.events.participants.index', $event)
            ->with('success', 'Participant removed successfully.');
    }

    public function importForm(Event $event): Response
    {
        $this->authorize('create', [EventParticipant::class, $event]);

        return Inertia::render('Admin/Events/Participants/Import', [
            'event' => $this->eventContext($event),
            'participantUnitLabel' => $event->participant_unit_label?->label() ?? 'Participant',
            'participantTypes' => EventParticipantType::values(),
            'statuses' => EventParticipantStatus::values(),
        ]);
    }

    public function import(
        ImportEventParticipantsRequest $request,
        Event $event,
        EventParticipantCsvImporter $importer,
    ): RedirectResponse {
        try {
            $result = $importer->import($event, $request->file('file'));
        } catch (ValidationException $exception) {
            return redirect()
                ->route('admin.events.participants.import', $event)
                ->withErrors($exception->errors())
                ->withInput();
        }

        return redirect()
            ->route('admin.events.participants.index', $event)
            ->with('success', "{$result['created']} participant(s) imported successfully.");
    }

    public function importTemplate(Event $event): StreamedResponse
    {
        $this->authorize('create', [EventParticipant::class, $event]);

        $defaultType = $this->defaultParticipantType($event);

        return response()->streamDownload(function () use ($defaultType) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['type', 'name', 'code', 'branch_id', 'status']);
            fputcsv($handle, [$defaultType, 'Example Unit', 'EXM', '', EventParticipantStatus::Active->value]);
            fclose($handle);
        }, 'participants-template.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function eventContext(Event $event): array
    {
        return [
            'id' => $event->id,
            'name' => $event->name,
            'slug' => $event->slug,
            'edition_year' => $event->edition_year,
            'participant_unit_label' => $event->participant_unit_label?->value,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function listPayload(EventParticipant $participant): array
    {
        return [
            'id' => $participant->id,
            'type' => $participant->type->value,
            'name' => $participant->name,
            'code' => $participant->code,
            'status' => $participant->status->value,
            'sport_entries_count' => $participant->sport_entries_count,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function detailPayload(EventParticipant $participant): array
    {
        return [
            'id' => $participant->id,
            'type' => $participant->type->value,
            'name' => $participant->name,
            'code' => $participant->code,
            'status' => $participant->status->value,
            'teams_count' => $participant->teams_count,
            'athletes_count' => $participant->athletes_count,
            'sport_entries' => $participant->sportEntries->map(fn ($entry) => [
                'id' => $entry->id,
                'sport' => $entry->sport?->only(['id', 'name', 'slug']),
                'sport_category' => $entry->sportCategory?->only(['id', 'name']),
                'sport_division' => $entry->sportDivision?->only(['id', 'name']),
                'status' => $entry->status->value,
                'notes' => $entry->notes,
            ]),
        ];
    }

    /**
     * @return list<array{id: int, name: string, code: string|null}>
     */
    private function branchOptions(Event $event): array
    {
        return Branch::query()
            ->where('organization_id', $event->organization_id)
            ->orderBy('name')
            ->get(['id', 'name', 'code'])
            ->map(fn (Branch $branch) => $branch->only(['id', 'name', 'code']))
            ->values()
            ->all();
    }

    private function defaultParticipantType(Event $event): string
    {
        return match ($event->participant_unit_label) {
            ParticipantUnitLabel::Faculty => EventParticipantType::Faculty->value,
            ParticipantUnitLabel::State => EventParticipantType::State->value,
            ParticipantUnitLabel::Country => EventParticipantType::Country->value,
            ParticipantUnitLabel::Club => EventParticipantType::Club->value,
            default => EventParticipantType::Other->value,
        };
    }
}