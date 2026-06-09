<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreVenueRequest;
use App\Http\Requests\Admin\UpdateVenueRequest;
use App\Models\Event;
use App\Models\Facility;
use App\Models\MatchGame;
use App\Models\Organization;
use App\Models\Venue;
use App\Support\OrganizationContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class VenueController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Venue::class);

        $organization = $this->resolveOrganization($request);

        $venues = Venue::query()
            ->where('organization_id', $organization->id)
            ->withCount('facilities')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();
                $query->where(function ($builder) use ($search) {
                    $builder
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Venue $venue) => $this->venueListPayload($venue));

        return Inertia::render('Admin/Venues/Index', [
            'venues' => $venues,
            'organization' => $organization->only(['id', 'name', 'slug']),
            'filters' => [
                'search' => $request->string('search')->toString(),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $organization = $this->resolveOrganization($request);
        $this->authorize('create', [Venue::class, $organization]);

        return Inertia::render('Admin/Venues/Create', [
            'organization' => $organization->only(['id', 'name', 'slug']),
        ]);
    }

    public function store(StoreVenueRequest $request): RedirectResponse
    {
        $organization = $this->resolveOrganization($request);
        $validated = $request->validated();
        $slug = $validated['slug'] ?? $this->uniqueSlug($validated['name'], $organization->id);

        $venue = Venue::create([
            'organization_id' => $organization->id,
            'name' => $validated['name'],
            'slug' => $slug,
            'address' => $validated['address'] ?? null,
            'capacity' => $validated['capacity'] ?? null,
            'timezone' => $validated['timezone'] ?? 'UTC',
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('admin.venues.show', $venue)
            ->with('success', 'Venue created successfully.');
    }

    public function show(Request $request, Venue $venue): Response
    {
        $this->authorize('view', $venue);
        $this->ensureVenueInOrganization($request, $venue);

        $venue->load('facilities');

        return Inertia::render('Admin/Venues/Show', [
            'venue' => $this->venueDetailPayload($venue),
            'facilityTypes' => \App\Enums\FacilityType::values(),
            'canManageFacilities' => $request->user()?->can('update', $venue) ?? false,
        ]);
    }

    public function edit(Request $request, Venue $venue): Response
    {
        $this->authorize('update', $venue);
        $this->ensureVenueInOrganization($request, $venue);

        return Inertia::render('Admin/Venues/Edit', [
            'venue' => $this->venueDetailPayload($venue),
        ]);
    }

    public function update(UpdateVenueRequest $request, Venue $venue): RedirectResponse
    {
        $this->ensureVenueInOrganization($request, $venue);

        $validated = $request->validated();
        $slug = $validated['slug'] ?? $this->uniqueSlug(
            $validated['name'],
            $venue->organization_id,
            $venue->id,
        );

        $venue->update([
            ...$validated,
            'slug' => $slug,
        ]);

        return redirect()->route('admin.venues.show', $venue)
            ->with('success', 'Venue updated successfully.');
    }

    public function destroy(Request $request, Venue $venue): RedirectResponse
    {
        $this->authorize('delete', $venue);
        $this->ensureVenueInOrganization($request, $venue);

        $venue->delete();

        return redirect()->route('admin.venues.index')
            ->with('success', 'Venue removed successfully.');
    }

    private function resolveOrganization(Request $request, ?Event $event = null): Organization
    {
        if ($event !== null) {
            return $event->organization;
        }

        return OrganizationContext::resolve($request);
    }

    private function ensureVenueInOrganization(Request $request, Venue $venue): void
    {
        $organization = $this->resolveOrganization($request);
        abort_unless($venue->organization_id === $organization->id, 404);
    }

    /**
     * @return array<string, mixed>
     */
    private function venueListPayload(Venue $venue): array
    {
        return [
            'id' => $venue->id,
            'name' => $venue->name,
            'slug' => $venue->slug,
            'address' => $venue->address,
            'capacity' => $venue->capacity,
            'timezone' => $venue->timezone,
            'facilities_count' => $venue->facilities_count,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function venueDetailPayload(Venue $venue): array
    {
        // POLISH-07: Basic availability - list upcoming bookings/matches using this venue
        $bookings = MatchGame::query()
            ->where('venue_id', $venue->id)
            ->whereNotNull('scheduled_at')
            ->with([
                'facility:id,name',
                'fixture.competition:id,name,sport_id,event_id',
                'fixture.competition.sport:id,name',
                'fixture.competition.event:id,name',
            ])
            ->orderBy('scheduled_at')
            ->limit(15)
            ->get()
            ->map(fn ($match) => [
                'id' => $match->id,
                'scheduled_at' => $match->scheduled_at?->toDateTimeString(),
                'duration_minutes' => $match->duration_minutes,
                'facility' => $match->facility?->only(['id', 'name']),
                'competition' => $match->fixture?->competition?->only(['id', 'name']),
                'sport' => $match->fixture?->competition?->sport?->only(['id', 'name']),
                'event' => $match->fixture?->competition?->event?->only(['id', 'name']),
            ]);

        return [
            'id' => $venue->id,
            'name' => $venue->name,
            'slug' => $venue->slug,
            'address' => $venue->address,
            'capacity' => $venue->capacity,
            'timezone' => $venue->timezone,
            'notes' => $venue->notes,
            'facilities' => $venue->facilities->map(fn ($facility) => [
                'id' => $facility->id,
                'name' => $facility->name,
                'slug' => $facility->slug,
                'type' => $facility->type->value,
                'capacity' => $facility->capacity,
                'sort_order' => $facility->sort_order,
            ]),
            'bookings' => $bookings,
        ];
    }

    private function uniqueSlug(string $name, int $organizationId, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 1;

        while (Venue::withTrashed()
            ->where('organization_id', $organizationId)
            ->where('slug', $slug)
            ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}