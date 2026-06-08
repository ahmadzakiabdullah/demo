<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OfficialType;
use App\Enums\RegistrationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreOfficialRequest;
use App\Http\Requests\Admin\UpdateOfficialRequest;
use App\Models\Event;
use App\Models\Official;
use App\Models\Registration;
use App\Models\Sport;
use App\Services\EligibilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OfficialController extends Controller
{
    public function index(Request $request, Event $event): Response
    {
        $this->authorize('view', $event);
        $this->authorize('viewAny', Official::class);

        $officials = Official::query()
            ->where('organization_id', $event->organization_id)
            ->whereHas('registrations', fn ($query) => $query->where('event_id', $event->id))
            ->with([
                'registrations' => fn ($query) => $query
                    ->where('event_id', $event->id)
                    ->with(['sport:id,name,slug', 'sportCategory:id,name', 'sportDivision:id,name']),
            ])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();
                $query->where(function ($builder) use ($search) {
                    $builder
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('certification_level', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), function ($query) use ($request, $event) {
                $query->whereHas('registrations', fn ($registrationQuery) => $registrationQuery
                    ->where('event_id', $event->id)
                    ->where('status', $request->string('status')->toString()));
            })
            ->when($request->filled('sport_id'), function ($query) use ($request, $event) {
                $query->whereHas('registrations', fn ($registrationQuery) => $registrationQuery
                    ->where('event_id', $event->id)
                    ->where('sport_id', $request->integer('sport_id')));
            })
            ->when($request->filled('type'), function ($query) use ($request) {
                $query->where('type', $request->string('type')->toString());
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Official $official) => $this->officialListPayload($official));

        return Inertia::render('Admin/Events/Officials/Index', [
            'event' => $event->only(['id', 'name', 'slug']),
            'officials' => $officials,
            'sports' => $event->sports()->orderBy('name')->get(['id', 'name', 'slug']),
            'statuses' => RegistrationStatus::values(),
            'types' => OfficialType::values(),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'status' => $request->string('status')->toString(),
                'sport_id' => $request->string('sport_id')->toString(),
                'type' => $request->string('type')->toString(),
            ],
        ]);
    }

    public function create(Event $event): Response
    {
        $this->authorize('create', [Official::class, $event]);

        return Inertia::render('Admin/Events/Officials/Create', [
            'event' => $event->only(['id', 'name', 'slug']),
            'sports' => $this->sportOptions($event),
            'types' => OfficialType::values(),
            'existingOfficials' => Official::query()
                ->where('organization_id', $event->organization_id)
                ->whereDoesntHave('registrations', fn ($query) => $query->where('event_id', $event->id))
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'type']),
        ]);
    }

    public function store(StoreOfficialRequest $request, Event $event): RedirectResponse
    {
        $validated = $request->validated();

        $official = $validated['existing_official_id'] ?? null
            ? Official::query()->findOrFail($validated['existing_official_id'])
            : Official::create([
                'organization_id' => $event->organization_id,
                'user_id' => $validated['user_id'] ?? null,
                'name' => $validated['name'],
                'email' => $validated['email'] ?? null,
                'type' => $validated['type'],
                'certification_level' => $validated['certification_level'] ?? null,
                'certification_expires_at' => $validated['certification_expires_at'] ?? null,
            ]);

        Registration::create([
            'event_id' => $event->id,
            'sport_id' => $validated['sport_id'],
            'registrable_type' => Official::class,
            'registrable_id' => $official->id,
            'sport_category_id' => $validated['sport_category_id'] ?? null,
            'sport_division_id' => $validated['sport_division_id'] ?? null,
            'status' => RegistrationStatus::Draft,
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('admin.events.officials.show', [$event, $official])
            ->with('success', 'Official registered successfully.');
    }

    public function show(Request $request, Event $event, Official $official, EligibilityService $eligibilityService): Response
    {
        $this->authorize('view', $official);
        abort_unless($official->organization_id === $event->organization_id, 404);

        $eventRegistrations = $official->registrations()
            ->where('event_id', $event->id)
            ->with(['sport', 'sportCategory', 'sportDivision'])
            ->get();

        abort_unless($eventRegistrations->isNotEmpty(), 404);

        $history = $official->registrations()
            ->with(['event:id,name,slug', 'sport:id,name'])
            ->where('event_id', '!=', $event->id)
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('Admin/Events/Officials/Show', [
            'event' => $event->only(['id', 'name', 'slug']),
            'official' => $this->officialDetailPayload($official),
            'registrations' => $eventRegistrations->map(fn (Registration $registration) => $this->registrationPayload(
                $registration,
                $eligibilityService,
            )),
            'history' => $history->map(fn (Registration $registration) => [
                'id' => $registration->id,
                'status' => $registration->status->value,
                'event' => $registration->event?->only(['id', 'name', 'slug']),
                'sport' => $registration->sport?->only(['id', 'name']),
                'approved_at' => $registration->approved_at?->toDateTimeString(),
            ]),
            'statuses' => RegistrationStatus::values(),
            'canManageRegistrations' => $eventRegistrations->contains(
                fn (Registration $registration) => $request->user()?->can('updateStatus', $registration) ?? false,
            ),
        ]);
    }

    public function edit(Event $event, Official $official): Response
    {
        $this->authorize('update', $official);
        abort_unless($official->organization_id === $event->organization_id, 404);

        return Inertia::render('Admin/Events/Officials/Edit', [
            'event' => $event->only(['id', 'name', 'slug']),
            'official' => $this->officialDetailPayload($official),
            'types' => OfficialType::values(),
        ]);
    }

    public function update(UpdateOfficialRequest $request, Event $event, Official $official): RedirectResponse
    {
        abort_unless($official->organization_id === $event->organization_id, 404);

        $official->update($request->validated());

        return redirect()->route('admin.events.officials.show', [$event, $official])
            ->with('success', 'Official updated successfully.');
    }

    public function destroy(Event $event, Official $official): RedirectResponse
    {
        $this->authorize('delete', $official);
        abort_unless($official->organization_id === $event->organization_id, 404);

        $official->delete();

        return redirect()->route('admin.events.officials.index', $event)
            ->with('success', 'Official removed successfully.');
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function sportOptions(Event $event): array
    {
        return $event->sports()
            ->with(['disciplines.categories.divisions'])
            ->orderBy('name')
            ->get()
            ->map(fn (Sport $sport) => [
                'id' => $sport->id,
                'name' => $sport->name,
                'disciplines' => $sport->disciplines->map(fn ($discipline) => [
                    'id' => $discipline->id,
                    'name' => $discipline->name,
                    'categories' => $discipline->categories->map(fn ($category) => [
                        'id' => $category->id,
                        'name' => $category->name,
                        'gender' => $category->gender->value,
                        'min_age' => $category->min_age,
                        'max_age' => $category->max_age,
                        'divisions' => $category->divisions->map(fn ($division) => [
                            'id' => $division->id,
                            'name' => $division->name,
                        ]),
                    ]),
                ]),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function officialListPayload(Official $official): array
    {
        return [
            'id' => $official->id,
            'name' => $official->name,
            'email' => $official->email,
            'type' => $official->type->value,
            'certification_level' => $official->certification_level,
            'certification_expires_at' => $official->certification_expires_at?->toDateString(),
            'registrations' => $official->registrations->map(fn (Registration $registration) => [
                'id' => $registration->id,
                'status' => $registration->status->value,
                'sport' => $registration->sport?->only(['id', 'name', 'slug']),
                'sport_category' => $registration->sportCategory?->only(['id', 'name']),
                'sport_division' => $registration->sportDivision?->only(['id', 'name']),
            ]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function officialDetailPayload(Official $official): array
    {
        return [
            'id' => $official->id,
            'name' => $official->name,
            'email' => $official->email,
            'type' => $official->type->value,
            'certification_level' => $official->certification_level,
            'certification_expires_at' => $official->certification_expires_at?->toDateString(),
            'user_id' => $official->user_id,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function registrationPayload(Registration $registration, EligibilityService $eligibilityService): array
    {
        return [
            'id' => $registration->id,
            'status' => $registration->status->value,
            'notes' => $registration->notes,
            'rejected_reason' => $registration->rejected_reason,
            'submitted_at' => $registration->submitted_at?->toDateTimeString(),
            'verified_at' => $registration->verified_at?->toDateTimeString(),
            'approved_at' => $registration->approved_at?->toDateTimeString(),
            'sport' => $registration->sport?->only(['id', 'name', 'slug']),
            'sport_category' => $registration->sportCategory?->only(['id', 'name', 'slug']),
            'sport_division' => $registration->sportDivision?->only(['id', 'name', 'slug']),
            'eligibility_issues' => $registration->registrable instanceof Official
                ? $eligibilityService->officialIssues($registration->registrable)
                : [],
        ];
    }
}