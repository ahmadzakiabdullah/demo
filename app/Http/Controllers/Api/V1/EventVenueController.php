<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AttachEventVenueRequest;
use App\Http\Requests\Admin\LinkEventSportVenueRequest;
use App\Http\Resources\Api\V1\VenueResource;
use App\Models\Event;
use App\Models\Sport;
use App\Models\Venue;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EventVenueController extends Controller
{
    public function index(Request $request, Event $event): JsonResponse
    {
        $this->authorize('view', $event);
        $this->authorize('viewAny', Venue::class);

        $venues = $event->venues()
            ->with(['facilities'])
            ->withCount('facilities')
            ->orderByDesc('event_venue.is_primary')
            ->orderBy('venues.name')
            ->get();

        return ApiResponse::success(VenueResource::collection($venues));
    }

    public function store(AttachEventVenueRequest $request, Event $event): JsonResponse
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

        $venue = $event->venues()
            ->where('venues.id', $validated['venue_id'])
            ->with(['facilities'])
            ->firstOrFail();

        return ApiResponse::success(new VenueResource($venue), 'Venue attached.', 201);
    }

    public function show(Event $event, Venue $venue): JsonResponse
    {
        $this->authorize('view', $event);
        abort_unless($venue->organization_id === $event->organization_id, 404);
        abort_unless($event->venues()->where('venues.id', $venue->id)->exists(), 404);

        $linkedSportIds = DB::table('event_sport_venue')
            ->where('event_id', $event->id)
            ->where('venue_id', $venue->id)
            ->pluck('sport_id');

        $venue->load([
            'facilities',
            'sports' => fn ($query) => $query->whereIn('sports.id', $linkedSportIds),
        ]);

        return ApiResponse::success(new VenueResource($venue));
    }

    public function destroy(Event $event, Venue $venue): JsonResponse
    {
        $this->authorize('attach', [Venue::class, $event]);
        abort_unless($venue->organization_id === $event->organization_id, 404);

        DB::table('event_sport_venue')
            ->where('event_id', $event->id)
            ->where('venue_id', $venue->id)
            ->delete();

        $event->venues()->detach($venue->id);

        return ApiResponse::success(message: 'Venue detached.');
    }

    public function storeSport(LinkEventSportVenueRequest $request, Event $event, Venue $venue): JsonResponse
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

        $sport = Sport::query()->findOrFail($request->validated('sport_id'));

        return ApiResponse::success([
            'sport' => $sport->only(['id', 'name', 'slug']),
            'venue_id' => $venue->id,
        ], 'Sport linked.', 201);
    }

    public function destroySport(Event $event, Venue $venue, Sport $sport): JsonResponse
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

        return ApiResponse::success(message: 'Sport unlinked.');
    }
}