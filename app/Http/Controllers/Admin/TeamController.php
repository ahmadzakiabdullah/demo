<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RegistrationStatus;
use App\Enums\TeamMemberRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTeamAthleteRequest;
use App\Http\Requests\Admin\StoreTeamRequest;
use App\Http\Requests\Admin\UpdateTeamRequest;
use App\Models\Athlete;
use App\Models\Event;
use App\Models\Registration;
use App\Models\Sport;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TeamController extends Controller
{
    public function index(Request $request, Event $event): Response
    {
        $this->authorize('view', $event);
        $this->authorize('viewAny', Team::class);

        $teams = $event->teams()
            ->with([
                'sport:id,name,slug',
                'coach:id,name',
                'manager:id,name',
                'registrations' => fn ($query) => $query->where('event_id', $event->id),
            ])
            ->withCount('athletes')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();
                $query->where(function ($builder) use ($search) {
                    $builder
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), function ($query) use ($request, $event) {
                $query->whereHas('registrations', fn ($registrationQuery) => $registrationQuery
                    ->where('event_id', $event->id)
                    ->where('status', $request->string('status')->toString()));
            })
            ->when($request->filled('sport_id'), fn ($query) => $query->where('sport_id', $request->integer('sport_id')))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Team $team) => $this->teamListPayload($team));

        return Inertia::render('Admin/Events/Teams/Index', [
            'event' => $event->only(['id', 'name', 'slug']),
            'teams' => $teams,
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
        $this->authorize('create', [Team::class, $event]);

        return Inertia::render('Admin/Events/Teams/Create', [
            'event' => $event->only(['id', 'name', 'slug']),
            'sports' => $this->sportOptions($event),
            'organizationMembers' => $this->organizationMembers($event),
        ]);
    }

    public function store(StoreTeamRequest $request, Event $event): RedirectResponse
    {
        $validated = $request->validated();
        $slug = $validated['slug'] ?? $this->uniqueSlug(
            $validated['name'],
            $event->id,
            (int) $validated['sport_id'],
        );

        $team = Team::create([
            'organization_id' => $event->organization_id,
            'event_id' => $event->id,
            'sport_id' => $validated['sport_id'],
            'name' => $validated['name'],
            'slug' => $slug,
            'coach_user_id' => $validated['coach_user_id'] ?? null,
            'manager_user_id' => $validated['manager_user_id'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        Registration::create([
            'event_id' => $event->id,
            'sport_id' => $validated['sport_id'],
            'registrable_type' => Team::class,
            'registrable_id' => $team->id,
            'sport_category_id' => $validated['sport_category_id'] ?? null,
            'sport_division_id' => $validated['sport_division_id'] ?? null,
            'status' => RegistrationStatus::Draft,
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('admin.events.teams.show', [$event, $team])
            ->with('success', 'Team registered successfully.');
    }

    public function show(Request $request, Event $event, Team $team): Response
    {
        $this->authorize('view', $team);
        abort_unless($team->event_id === $event->id, 404);

        $team->load([
            'sport',
            'coach:id,name,email',
            'manager:id,name,email',
            'athletes',
            'registrations' => fn ($query) => $query
                ->where('event_id', $event->id)
                ->with(['sportCategory', 'sportDivision']),
        ]);

        $availableAthletes = Athlete::query()
            ->where('organization_id', $event->organization_id)
            ->whereNotIn('id', $team->athletes->pluck('id'))
            ->orderBy('name')
            ->get(['id', 'name', 'id_number']);

        return Inertia::render('Admin/Events/Teams/Show', [
            'event' => $event->only(['id', 'name', 'slug']),
            'team' => $this->teamDetailPayload($team),
            'registrations' => $team->registrations->map(fn (Registration $registration) => [
                'id' => $registration->id,
                'status' => $registration->status->value,
                'notes' => $registration->notes,
                'rejected_reason' => $registration->rejected_reason,
                'submitted_at' => $registration->submitted_at?->toDateTimeString(),
                'verified_at' => $registration->verified_at?->toDateTimeString(),
                'approved_at' => $registration->approved_at?->toDateTimeString(),
                'sport_category' => $registration->sportCategory?->only(['id', 'name', 'slug']),
                'sport_division' => $registration->sportDivision?->only(['id', 'name', 'slug']),
            ]),
            'availableAthletes' => $availableAthletes,
            'memberRoles' => TeamMemberRole::values(),
            'statuses' => RegistrationStatus::values(),
            'canManageRoster' => $request->user()?->can('manageRoster', $team) ?? false,
            'canManageRegistrations' => $team->registrations->contains(
                fn (Registration $registration) => $request->user()?->can('updateStatus', $registration) ?? false,
            ),
        ]);
    }

    public function edit(Event $event, Team $team): Response
    {
        $this->authorize('update', $team);
        abort_unless($team->event_id === $event->id, 404);

        return Inertia::render('Admin/Events/Teams/Edit', [
            'event' => $event->only(['id', 'name', 'slug']),
            'team' => $this->teamDetailPayload($team),
            'organizationMembers' => $this->organizationMembers($event),
        ]);
    }

    public function update(UpdateTeamRequest $request, Event $event, Team $team): RedirectResponse
    {
        abort_unless($team->event_id === $event->id, 404);

        $team->update($request->validated());

        return redirect()->route('admin.events.teams.show', [$event, $team])
            ->with('success', 'Team updated successfully.');
    }

    public function destroy(Event $event, Team $team): RedirectResponse
    {
        $this->authorize('delete', $team);
        abort_unless($team->event_id === $event->id, 404);

        $team->delete();

        return redirect()->route('admin.events.teams.index', $event)
            ->with('success', 'Team removed successfully.');
    }

    public function storeAthlete(StoreTeamAthleteRequest $request, Event $event, Team $team): RedirectResponse
    {
        abort_unless($team->event_id === $event->id, 404);

        $validated = $request->validated();

        $team->athletes()->attach($validated['athlete_id'], [
            'role' => $validated['role'],
            'jersey_number' => $validated['jersey_number'] ?? null,
        ]);

        return back()->with('success', 'Athlete added to roster.');
    }

    public function destroyAthlete(Event $event, Team $team, Athlete $athlete): RedirectResponse
    {
        $this->authorize('manageRoster', $team);
        abort_unless($team->event_id === $event->id, 404);

        $team->athletes()->detach($athlete->id);

        return back()->with('success', 'Athlete removed from roster.');
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

    /**
     * @return array<string, mixed>
     */
    private function teamListPayload(Team $team): array
    {
        $registration = $team->registrations->first();

        return [
            'id' => $team->id,
            'name' => $team->name,
            'slug' => $team->slug,
            'sport' => $team->sport?->only(['id', 'name', 'slug']),
            'coach' => $team->coach?->only(['id', 'name']),
            'manager' => $team->manager?->only(['id', 'name']),
            'athletes_count' => $team->athletes_count,
            'registration_status' => $registration?->status->value,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function teamDetailPayload(Team $team): array
    {
        return [
            'id' => $team->id,
            'name' => $team->name,
            'slug' => $team->slug,
            'notes' => $team->notes,
            'sport' => $team->sport?->only(['id', 'name', 'slug']),
            'coach' => $team->coach?->only(['id', 'name', 'email']),
            'manager' => $team->manager?->only(['id', 'name', 'email']),
            'athletes' => $team->athletes->map(fn (Athlete $athlete) => [
                'id' => $athlete->id,
                'name' => $athlete->name,
                'id_number' => $athlete->id_number,
                'role' => $athlete->pivot->role,
                'jersey_number' => $athlete->pivot->jersey_number,
            ]),
        ];
    }

    private function uniqueSlug(string $name, int $eventId, int $sportId): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 1;

        while (Team::withTrashed()
            ->where('event_id', $eventId)
            ->where('sport_id', $sportId)
            ->where('slug', $slug)
            ->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}