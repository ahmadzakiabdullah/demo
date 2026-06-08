<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMedalCeremonyRequest;
use App\Models\Event;
use App\Models\MedalCeremony;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class MedalCeremonyController extends Controller
{
    public function index(Event $event): Response
    {
        $this->authorize('view', $event);

        $ceremonies = MedalCeremony::query()
            ->where('event_id', $event->id)
            ->with(['sport:id,name', 'venue:id,name'])
            ->orderBy('scheduled_at')
            ->get()
            ->map(fn (MedalCeremony $ceremony) => [
                'id' => $ceremony->id,
                'name' => $ceremony->name,
                'scheduled_at' => $ceremony->scheduled_at?->toDateTimeString(),
                'duration_minutes' => $ceremony->duration_minutes,
                'notes' => $ceremony->notes,
                'sport' => $ceremony->sport?->only(['id', 'name']),
                'venue' => $ceremony->venue?->only(['id', 'name']),
            ]);

        return Inertia::render('Admin/Events/MedalCeremonies/Index', [
            'event' => $event->only(['id', 'name', 'slug']),
            'ceremonies' => $ceremonies,
            'sports' => $event->sports()->orderBy('name')->get(['id', 'name']),
            'venues' => $event->venues()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreMedalCeremonyRequest $request, Event $event): RedirectResponse
    {
        MedalCeremony::query()->create([
            'organization_id' => $event->organization_id,
            'event_id' => $event->id,
            'sport_id' => $request->validated('sport_id'),
            'venue_id' => $request->validated('venue_id'),
            'name' => $request->validated('name'),
            'scheduled_at' => $request->validated('scheduled_at'),
            'duration_minutes' => $request->validated('duration_minutes') ?? 60,
            'notes' => $request->validated('notes'),
        ]);

        return back()->with('success', 'Medal ceremony scheduled.');
    }

    public function destroy(Event $event, MedalCeremony $ceremony): RedirectResponse
    {
        $this->authorize('update', $event);
        abort_unless($ceremony->event_id === $event->id, 404);

        $ceremony->delete();

        return back()->with('success', 'Medal ceremony removed.');
    }
}