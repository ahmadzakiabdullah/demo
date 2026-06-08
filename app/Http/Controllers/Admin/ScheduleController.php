<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Models\Event;
use App\Models\MatchGame;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class ScheduleController extends Controller
{
    public function index(Request $request, Event $event): Response
    {
        $this->authorize('view', $event);
        $this->authorize('viewAny', Competition::class);

        $startDate = $request->filled('date')
            ? Carbon::parse($request->string('date')->toString())->startOfDay()
            : ($event->starts_at?->copy()->startOfDay() ?? now()->startOfDay());

        $endDate = $startDate->copy()->addDays(6)->endOfDay();

        $matches = MatchGame::query()
            ->whereHas('fixture.competition', fn ($query) => $query->where('event_id', $event->id))
            ->whereNotNull('scheduled_at')
            ->whereBetween('scheduled_at', [$startDate, $endDate])
            ->with([
                'venue:id,name',
                'facility:id,name',
                'participants.participant',
                'fixture.competition.sport:id,name',
                'fixture.competition:id,name,sport_id,event_id',
            ])
            ->when($request->filled('sport_id'), function ($query) use ($request) {
                $query->whereHas('fixture.competition', fn ($competition) => $competition
                    ->where('sport_id', $request->integer('sport_id')));
            })
            ->orderBy('scheduled_at')
            ->get()
            ->map(fn (MatchGame $match) => [
                'id' => $match->id,
                'scheduled_at' => $match->scheduled_at?->toDateTimeString(),
                'duration_minutes' => $match->duration_minutes,
                'status' => $match->status->value,
                'venue' => $match->venue?->only(['id', 'name']),
                'facility' => $match->facility?->only(['id', 'name']),
                'competition' => $match->fixture?->competition?->only(['id', 'name']),
                'sport' => $match->fixture?->competition?->sport?->only(['id', 'name']),
                'fixture' => $match->fixture?->only(['id', 'name', 'round']),
                'participants' => $match->participants->map(fn ($participant) => [
                    'side' => $participant->side->value,
                    'name' => $participant->participant?->name,
                ]),
            ]);

        $days = collect();
        for ($day = $startDate->copy(); $day->lte($endDate->copy()->startOfDay()); $day->addDay()) {
            $dateKey = $day->toDateString();
            $dayMatches = $matches->filter(
                fn ($match) => Carbon::parse($match['scheduled_at'])->toDateString() === $dateKey,
            )->values();

            $days->push([
                'date' => $dateKey,
                'label' => $day->format('l, M j'),
                'matches' => $dayMatches,
            ]);
        }

        return Inertia::render('Admin/Events/Schedule/Index', [
            'event' => $event->only(['id', 'name', 'slug', 'starts_at', 'ends_at']),
            'days' => $days->values(),
            'sports' => $event->sports()->orderBy('name')->get(['id', 'name', 'slug']),
            'filters' => [
                'date' => $startDate->toDateString(),
                'sport_id' => $request->string('sport_id')->toString(),
            ],
        ]);
    }
}