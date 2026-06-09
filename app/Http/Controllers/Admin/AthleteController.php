<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RegistrationStatus;
use App\Enums\SportGender;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAthleteRequest;
use App\Http\Requests\Admin\UpdateAthleteRequest;
use App\Models\Athlete;
use App\Models\Event;
use App\Models\Registration;
use App\Models\Sport;
use App\Services\EligibilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AthleteController extends Controller
{
    public function index(Request $request, Event $event): Response
    {
        $this->authorize('view', $event);
        $this->authorize('viewAny', Athlete::class);

        $athletes = Athlete::query()
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
                        ->orWhere('id_number', 'like', "%{$search}%")
                        ->orWhere('nationality', 'like', "%{$search}%");
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
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Athlete $athlete) => $this->athleteListPayload($athlete));

        return Inertia::render('Admin/Events/Athletes/Index', [
            'event' => $event->only(['id', 'name', 'slug']),
            'athletes' => $athletes,
            'sports' => $event->sports()->orderBy('name')->get(['id', 'name', 'slug']),
            'statuses' => RegistrationStatus::values(),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'status' => $request->string('status')->toString(),
                'sport_id' => $request->string('sport_id')->toString(),
            ],
        ]);
    }

    public function create(Event $event): Response
    {
        $this->authorize('create', [Athlete::class, $event]);

        return Inertia::render('Admin/Events/Athletes/Create', [
            'event' => $event->only(['id', 'name', 'slug']),
            'sports' => $this->sportOptions($event),
            'genders' => SportGender::values(),
            'existingAthletes' => Athlete::query()
                ->where('organization_id', $event->organization_id)
                ->whereDoesntHave('registrations', fn ($query) => $query->where('event_id', $event->id))
                ->orderBy('name')
                ->get(['id', 'name', 'id_number']),
        ]);
    }

    public function store(StoreAthleteRequest $request, Event $event, EligibilityService $eligibilityService): RedirectResponse
    {
        $validated = $request->validated();

        $athlete = $validated['existing_athlete_id'] ?? null
            ? Athlete::query()->findOrFail($validated['existing_athlete_id'])
            : Athlete::create([
                'organization_id' => $event->organization_id,
                'user_id' => $validated['user_id'] ?? null,
                'name' => $validated['name'],
                'dob' => $validated['dob'] ?? null,
                'gender' => $validated['gender'] ?? null,
                'nationality' => $validated['nationality'] ?? null,
                'id_number' => $validated['id_number'] ?? null,
                'medical_clearance' => $validated['medical_clearance'] ?? false,
                'weight' => $validated['weight'] ?? null,
            ]);

        $category = ($validated['sport_category_id'] ?? null) ? SportCategory::find($validated['sport_category_id']) : null;
        $issues = $eligibilityService->issues($athlete, $category, $event);

        if ($issues !== []) {
            // Allow draft but record issues in notes for review
            $notes = ($validated['notes'] ?? '') . ' [Eligibility issues: ' . implode('; ', $issues) . ']';
        } else {
            $notes = $validated['notes'] ?? null;
        }

        Registration::create([
            'event_id' => $event->id,
            'sport_id' => $validated['sport_id'],
            'registrable_type' => Athlete::class,
            'registrable_id' => $athlete->id,
            'sport_category_id' => $validated['sport_category_id'] ?? null,
            'sport_division_id' => $validated['sport_division_id'] ?? null,
            'status' => RegistrationStatus::Draft,
            'notes' => $notes,
        ]);

        $message = $issues !== [] 
            ? 'Athlete registered with eligibility warnings. Review before approval.' 
            : 'Athlete registered successfully.';

        return redirect()->route('admin.events.athletes.show', [$event, $athlete])
            ->with('success', $message);
    }

    public function show(Request $request, Event $event, Athlete $athlete, EligibilityService $eligibilityService): Response
    {
        $this->authorize('view', $athlete);
        abort_unless($athlete->organization_id === $event->organization_id, 404);

        $eventRegistrations = $athlete->registrations()
            ->where('event_id', $event->id)
            ->with(['sport', 'sportCategory', 'sportDivision'])
            ->get();

        abort_unless($eventRegistrations->isNotEmpty(), 404);

        $history = $athlete->registrations()
            ->with(['event:id,name,slug', 'sport:id,name'])
            ->where('event_id', '!=', $event->id)
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('Admin/Events/Athletes/Show', [
            'event' => $event->only(['id', 'name', 'slug']),
            'athlete' => $this->athleteDetailPayload($athlete),
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

    public function edit(Event $event, Athlete $athlete): Response
    {
        $this->authorize('update', $athlete);
        abort_unless($athlete->organization_id === $event->organization_id, 404);

        return Inertia::render('Admin/Events/Athletes/Edit', [
            'event' => $event->only(['id', 'name', 'slug']),
            'athlete' => $this->athleteDetailPayload($athlete),
            'genders' => SportGender::values(),
        ]);
    }

    public function update(UpdateAthleteRequest $request, Event $event, Athlete $athlete): RedirectResponse
    {
        abort_unless($athlete->organization_id === $event->organization_id, 404);

        $athlete->update($request->validated());

        return redirect()->route('admin.events.athletes.show', [$event, $athlete])
            ->with('success', 'Athlete updated successfully.');
    }

    public function destroy(Event $event, Athlete $athlete): RedirectResponse
    {
        $this->authorize('delete', $athlete);
        abort_unless($athlete->organization_id === $event->organization_id, 404);

        $athlete->delete();

        return redirect()->route('admin.events.athletes.index', $event)
            ->with('success', 'Athlete removed successfully.');
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
    private function athleteListPayload(Athlete $athlete): array
    {
        return [
            'id' => $athlete->id,
            'name' => $athlete->name,
            'gender' => $athlete->gender?->value,
            'nationality' => $athlete->nationality,
            'id_number' => $athlete->id_number,
            'medical_clearance' => $athlete->medical_clearance,
            'registrations' => $athlete->registrations->map(fn (Registration $registration) => [
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
    private function athleteDetailPayload(Athlete $athlete): array
    {
        return [
            'id' => $athlete->id,
            'name' => $athlete->name,
            'dob' => $athlete->dob?->toDateString(),
            'gender' => $athlete->gender?->value,
            'nationality' => $athlete->nationality,
            'id_number' => $athlete->id_number,
            'medical_clearance' => $athlete->medical_clearance,
            'user_id' => $athlete->user_id,
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
            'eligibility_issues' => $registration->registrable instanceof Athlete
                ? $eligibilityService->issues($registration->registrable, $registration->sportCategory, $registration->event)
                : [],
        ];
    }
}