<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AttachEventVenueRequest;
use App\Http\Requests\Admin\LinkEventSportVenueRequest;
use App\Models\Event;
use App\Models\Sport;
use App\Models\Venue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class EventVenueController extends Controller
{
    public function index(Request $request, Event $event): Response
    {
        $this->authorize('view', $event);
        $this->authorize('viewAny', Venue::class);

        $attachedVenues = $event->venues()
            ->withCount('facilities')
            ->orderByDesc('event_venue.is_primary')
            ->orderBy('venues.name')
            ->get()
            ->map(fn (Venue $venue) => [
                'id' => $venue->id,
                'name' => $venue->name,
                'slug' => $venue->slug,
                'address' => $venue->address,
                'capacity' => $venue->capacity,
                'facilities_count' => $venue->facilities_count,
                'is_primary' => (bool) $venue->pivot->is_primary,
                'notes' => $venue->pivot->notes,
            ]);

        $attachedIds = $attachedVenues->pluck('id');

        $availableVenues = Venue::query()
            ->where('organization_id', $event->organization_id)
            ->when($attachedIds->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $attachedIds))
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'address']);

        return Inertia::render('Admin/Events/Venues/Index', [
            'event' => $event->only(['id', 'name', 'slug']),
            'venues' => $attachedVenues,
            'availableVenues' => $availableVenues,
            'canManageVenues' => $request->user()?->can('attach', [Venue::class, $event]) ?? false,
        ]);
    }

    public function show(Request $request, Event $event, Venue $venue): Response
    {
        $this->authorize('view', $event);
        abort_unless($venue->organization_id === $event->organization_id, 404);
        abort_unless($event->venues()->where('venues.id', $venue->id)->exists(), 404);

        $venue->load('facilities');

        $linkedSportIds = DB::table('event_sport_venue')
            ->where('event_id', $event->id)
            ->where('venue_id', $venue->id)
            ->pluck('sport_id');

        $linkedSports = Sport::query()
            ->whereIn('id', $linkedSportIds)
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        $availableSports = $event->sports()
            ->when($linkedSportIds->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $linkedSportIds))
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        $pivot = $event->venues()->where('venues.id', $venue->id)->first()?->pivot;

        return Inertia::render('Admin/Events/Venues/Show', [
            'event' => $event->only(['id', 'name', 'slug']),
            'venue' => [
                'id' => $venue->id,
                'name' => $venue->name,
                'slug' => $venue->slug,
                'address' => $venue->address,
                'capacity' => $venue->capacity,
                'timezone' => $venue->timezone,
                'notes' => $venue->notes,
                'is_primary' => (bool) ($pivot?->is_primary ?? false),
                'event_notes' => $pivot?->notes,
                'facilities' => $venue->facilities->map(fn ($facility) => [
                    'id' => $facility->id,
                    'name' => $facility->name,
                    'type' => $facility->type->value,
                    'capacity' => $facility->capacity,
                ]),
            ],
            'linkedSports' => $linkedSports,
            'availableSports' => $availableSports,
            'canManageVenues' => $request->user()?->can('manageAtEvent', [Venue::class, $event]) ?? false,
        ]);
    }

    public function store(AttachEventVenueRequest $request, Event $event): RedirectResponse
    {
        $validated = $request->validated();

        if ($validated['is_primary'] ?? false) {
            DB::table('event_venue')
                ->where('event_id', $event->id)
                ->update(['is_primary' => false]);
        }

        $event->venues()->attach($validated['venue_id'], [
            'is_primary' => $validated['is_primary'] ?? false,
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('admin.events.venues.show', [$event, $validated['venue_id']])
            ->with('success', 'Venue attached to event.');
    }

    public function destroy(Event $event, Venue $venue): RedirectResponse
    {
        $this->authorize('attach', [Venue::class, $event]);
        abort_unless($venue->organization_id === $event->organization_id, 404);

        DB::table('event_sport_venue')
            ->where('event_id', $event->id)
            ->where('venue_id', $venue->id)
            ->delete();

        $event->venues()->detach($venue->id);

        return redirect()->route('admin.events.venues.index', $event)
            ->with('success', 'Venue detached from event.');
    }

    public function storeSport(LinkEventSportVenueRequest $request, Event $event, Venue $venue): RedirectResponse
    {
        abort_unless($venue->organization_id === $event->organization_id, 404);
        abort_unless($event->venues()->where('venues.id', $venue->id)->exists(), 404);

        DB::table('event_sport_venue')->insert([
            'event_id' => $event->id,
            'sport_id' => $request->validated('sport_id'),
            'venue_id' => $venue->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Sport linked to venue.');
    }

    public function destroySport(Event $event, Venue $venue, Sport $sport): RedirectResponse
    {
        $this->authorize('manageAtEvent', [Venue::class, $event]);
        abort_unless($venue->organization_id === $event->organization_id, 404);
        abort_unless($sport->event_id === $event->id, 404);
        abort_unless($event->venues()->where('venues.id', $venue->id)->exists(), 404);

        DB::table('event_sport_venue')
            ->where('event_id', $event->id)
            ->where('venue_id', $venue->id)
            ->where('sport_id', $sport->id)
            ->delete();

        return back()->with('success', 'Sport unlinked from venue.');
    }
}