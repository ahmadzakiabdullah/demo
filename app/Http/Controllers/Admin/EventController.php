<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EventAssignmentRole;
use App\Enums\EventCadence;
use App\Enums\EventStatus;
use App\Enums\ParticipantUnitLabel;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreEventAssignmentRequest;
use App\Http\Requests\Admin\StoreEventRequest;
use App\Http\Requests\Admin\UpdateEventRequest;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\EventSeries;
use App\Models\EventType;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Event::class);

        $user = $request->user();

        $events = Event::query()
            ->with(['organization:id,name,slug', 'eventType:id,name', 'eventCategory:id,name'])
            ->when(! $user->isSystemOwner(), fn ($query) => $this->scopeToAccessibleEvents($query, $user))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(function ($builder) use ($search) {
                    $builder
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('organization_id') && $user->isSystemOwner(), function ($query) use ($request) {
                $query->where('organization_id', $request->integer('organization_id'));
            })
            ->when($request->filled('edition_year'), fn ($query) => $query->where('edition_year', $request->integer('edition_year')))
            ->orderByDesc('edition_year')
            ->orderByDesc('starts_at')
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Event $event) => $this->eventListPayload($event));

        return Inertia::render('Admin/Events/Index', [
            'events' => $events,
            'filters' => [
                'search' => $request->string('search')->toString(),
                'status' => $request->string('status')->toString(),
                'organization_id' => $request->string('organization_id')->toString(),
                'edition_year' => $request->string('edition_year')->toString(),
            ],
            'statuses' => EventStatus::values(),
            'editionYears' => Event::query()
                ->when(! $user->isSystemOwner(), fn ($query) => $this->scopeToAccessibleEvents($query, $user))
                ->distinct()
                ->orderByDesc('edition_year')
                ->pluck('edition_year')
                ->filter()
                ->values()
                ->all(),
            'organizations' => $this->selectableOrganizations($user),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Event::class);

        $organizations = $this->selectableOrganizations($request->user());

        return Inertia::render('Admin/Events/Create', [
            'organizations' => $organizations,
            'eventTypes' => EventType::query()->orderBy('name')->get(['id', 'name', 'slug']),
            'eventCategories' => EventCategory::query()->orderBy('name')->get(['id', 'name', 'slug']),
            'statuses' => EventStatus::values(),
            'cadences' => EventCadence::values(),
            'participantUnitLabels' => ParticipantUnitLabel::values(),
            'defaultOrganizationId' => $organizations[0]['id'] ?? null,
            'defaultEditionYear' => (int) now()->format('Y'),
        ]);
    }

    public function store(StoreEventRequest $request): RedirectResponse
    {
        $slug = $request->validated('slug') ?: $this->uniqueSlug(
            $request->validated('name'),
            $request->integer('organization_id'),
        );

        $event = Event::create([
            ...$request->safe()->only([
                'organization_id',
                'event_type_id',
                'event_category_id',
                'event_series_id',
                'name',
                'edition_year',
                'cadence',
                'participant_unit_label',
                'location',
                'description',
                'starts_at',
                'ends_at',
            ]),
            'slug' => $slug,
            'status' => $request->validated('status'),
        ]);

        return redirect()->route('admin.events.show', $event)
            ->with('success', 'Event created successfully.');
    }

    public function show(Event $event): Response
    {
        $this->authorize('view', $event);

        $event->load([
            'organization:id,name,slug',
            'eventType:id,name,slug',
            'eventCategory:id,name,slug',
            'eventSeries:id,name,slug',
            'assignees:id,name,email',
        ])->loadCount(['sports', 'eventParticipants']);

        return Inertia::render('Admin/Events/Show', [
            'event' => $this->eventDetailPayload($event),
            'assignmentRoles' => EventAssignmentRole::values(),
            'organizationMembers' => $this->organizationMembers($event),
            'statuses' => EventStatus::values(),
        ]);
    }

    public function edit(Event $event): Response
    {
        $this->authorize('update', $event);

        $event->load(['organization:id,name']);

        return Inertia::render('Admin/Events/Edit', [
            'event' => [
                'id' => $event->id,
                'organization' => $event->organization?->only(['id', 'name']),
                'event_type_id' => $event->event_type_id,
                'event_category_id' => $event->event_category_id,
                'name' => $event->name,
                'slug' => $event->slug,
                'status' => $event->status->value,
                'location' => $event->location,
                'description' => $event->description,
                'starts_at' => $event->starts_at?->format('Y-m-d\TH:i'),
                'ends_at' => $event->ends_at?->format('Y-m-d\TH:i'),
                'edition_year' => $event->edition_year,
                'cadence' => $event->cadence?->value,
                'participant_unit_label' => $event->participant_unit_label?->value,
                'event_series_id' => $event->event_series_id,
            ],
            'eventTypes' => EventType::query()->orderBy('name')->get(['id', 'name', 'slug']),
            'eventCategories' => EventCategory::query()->orderBy('name')->get(['id', 'name', 'slug']),
            'eventSeries' => EventSeries::query()
                ->where('organization_id', $event->organization_id)
                ->orderBy('name')
                ->get(['id', 'name', 'slug']),
            'statuses' => EventStatus::values(),
            'cadences' => EventCadence::values(),
            'participantUnitLabels' => ParticipantUnitLabel::values(),
            'allowedTransitions' => $event->status->allowedTransitions(),
        ]);
    }

    public function update(UpdateEventRequest $request, Event $event): RedirectResponse
    {
        $event->update($request->validated());

        return redirect()->route('admin.events.show', $event)
            ->with('success', 'Event updated successfully.');
    }

    public function destroy(Event $event): RedirectResponse
    {
        $this->authorize('delete', $event);

        $event->delete();

        return redirect()->route('admin.events.index')
            ->with('success', 'Event deleted successfully.');
    }

    public function storeAssignment(StoreEventAssignmentRequest $request, Event $event): RedirectResponse
    {
        $event->assignees()->syncWithoutDetaching([
            $request->integer('user_id') => ['role' => $request->validated('role')],
        ]);

        return redirect()->route('admin.events.show', $event)
            ->with('success', 'Team member assigned successfully.');
    }

    public function destroyAssignment(Event $event, User $user): RedirectResponse
    {
        $this->authorize('manageAssignments', $event);

        $event->assignees()->detach($user->id);

        return redirect()->route('admin.events.show', $event)
            ->with('success', 'Assignment removed successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function eventListPayload(Event $event): array
    {
        return [
            'id' => $event->id,
            'name' => $event->name,
            'slug' => $event->slug,
            'status' => $event->status->value,
            'location' => $event->location,
            'starts_at' => $event->starts_at?->toDateString(),
            'ends_at' => $event->ends_at?->toDateString(),
            'edition_year' => $event->edition_year,
            'cadence' => $event->cadence?->value,
            'organization' => $event->organization?->only(['id', 'name', 'slug']),
            'event_type' => $event->eventType?->only(['id', 'name']),
            'event_category' => $event->eventCategory?->only(['id', 'name']),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function eventDetailPayload(Event $event): array
    {
        return [
            'id' => $event->id,
            'name' => $event->name,
            'slug' => $event->slug,
            'status' => $event->status->value,
            'location' => $event->location,
            'description' => $event->description,
            'starts_at' => $event->starts_at?->toDateTimeString(),
            'ends_at' => $event->ends_at?->toDateTimeString(),
            'organization' => $event->organization?->only(['id', 'name', 'slug']),
            'event_type' => $event->eventType?->only(['id', 'name', 'slug']),
            'event_category' => $event->eventCategory?->only(['id', 'name', 'slug']),
            'event_series' => $event->eventSeries?->only(['id', 'name', 'slug']),
            'edition_year' => $event->edition_year,
            'cadence' => $event->cadence?->value,
            'participant_unit_label' => $event->participant_unit_label?->value,
            'assignees' => $event->assignees->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->pivot->role,
            ]),
            'stats' => [
                'participants_count' => $event->event_participants_count
                    ?? $event->eventParticipants()->count(),
                'sport_entries_count' => \App\Models\ParticipantSportEntry::query()
                    ->whereHas('eventParticipant', fn ($q) => $q->where('event_id', $event->id))
                    ->count(),
                'registrations_count' => $event->registrations()
                    ->where('status', \App\Enums\RegistrationStatus::Approved)
                    ->count(),
                'fixtures_count' => $event->competitions()
                    ->withCount('fixtures')
                    ->get()
                    ->sum('fixtures_count'),
                'sports_count' => $event->sports_count ?? $event->sports()->count(),
            ],
            'setup_checklist' => $this->setupChecklist($event),
        ];
    }

    /**
     * @return list<array{id: int, name: string, slug?: string}>
     */
    private function selectableOrganizations(User $user): array
    {
        if ($user->isSystemOwner()) {
            return Organization::query()
                ->switchable()
                ->orderBy('name')
                ->get(['id', 'name', 'slug'])
                ->map(fn (Organization $organization) => $organization->only(['id', 'name', 'slug']))
                ->values()
                ->all();
        }

        return $user->organizations()
            ->orderBy('organizations.name')
            ->get(['organizations.id', 'organizations.name', 'organizations.slug'])
            ->map(fn (Organization $organization) => [
                'id' => $organization->id,
                'name' => $organization->name,
                'slug' => $organization->slug,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{id: int, name: string, email: string}>
     */
    private function organizationMembers(Event $event): array
    {
        return User::query()
            ->whereHas('organizations', fn ($query) => $query->where('organizations.id', $event->organization_id))
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(fn (User $user) => $user->only(['id', 'name', 'email']))
            ->values()
            ->all();
    }

    private function scopeToAccessibleEvents($query, User $user): void
    {
        $organizationIds = $user->organizations()->pluck('organizations.id');
        $assignedEventIds = $user->assignedEvents()->pluck('events.id');

        $query->where(function ($builder) use ($organizationIds, $assignedEventIds) {
            if ($organizationIds->isNotEmpty()) {
                $builder->whereIn('organization_id', $organizationIds);
            }

            if ($assignedEventIds->isNotEmpty()) {
                $builder->orWhereIn('id', $assignedEventIds);
            }

            if ($organizationIds->isEmpty() && $assignedEventIds->isEmpty()) {
                $builder->whereRaw('0 = 1');
            }
        });
    }

    private function uniqueSlug(string $name, int $organizationId): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 1;

        while (Event::withTrashed()
            ->where('organization_id', $organizationId)
            ->where('slug', $slug)
            ->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    /**
     * @return list<array{key: string, label: string, done: bool, href: string|null}>
     */
    private function setupChecklist(Event $event): array
    {
        $sportsCount = $event->sports_count ?? $event->sports()->count();
        $participantsCount = $event->event_participants_count ?? $event->eventParticipants()->count();
        $entriesCount = \App\Models\ParticipantSportEntry::query()
            ->whereHas('eventParticipant', fn ($q) => $q->where('event_id', $event->id))
            ->count();
        $teamsCount = $event->teams()->count();
        $fixturesCount = $event->competitions()->withCount('fixtures')->get()->sum('fixtures_count');

        // POLISH-04: Extend to full 8-step lifecycle
        $hasResults = $event->competitions()
            ->whereHas('fixtures.matches.result', function ($q) {
                $q->whereIn('status', ['confirmed', 'published']);
            })
            ->exists();

        $hasMedalsOrRankings = $event->medals()->exists() ||
            $event->competitions()->whereHas('rankings')->exists();

        return [
            [
                'key' => 'event',
                'label' => 'Event created',
                'done' => true,
                'href' => route('admin.events.edit', $event),
            ],
            [
                'key' => 'sports',
                'label' => 'Sports configured',
                'done' => $sportsCount > 0,
                'href' => route('admin.events.sports.index', $event),
            ],
            [
                'key' => 'participants',
                'label' => 'Participants registered',
                'done' => $participantsCount > 0,
                'href' => route('admin.events.participants.index', $event),
            ],
            [
                'key' => 'entries',
                'label' => 'Sport entries submitted',
                'done' => $entriesCount > 0,
                'href' => route('admin.events.participants.index', $event),
            ],
            [
                'key' => 'teams',
                'label' => 'Teams registered',
                'done' => $teamsCount > 0,
                'href' => route('admin.events.teams.index', $event),
            ],
            [
                'key' => 'schedule',
                'label' => 'Schedule built',
                'done' => $fixturesCount > 0,
                'href' => route('admin.events.schedule.index', $event),
            ],
            [
                'key' => 'results',
                'label' => 'Results recorded & published',
                'done' => $hasResults,
                'href' => route('admin.events.competitions.index', $event),
            ],
            [
                'key' => 'medals',
                'label' => 'Medals & rankings generated',
                'done' => $hasMedalsOrRankings,
                'href' => route('admin.events.medals.index', $event),
            ],
        ];
    }
}