<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Models\Event;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RankingController extends Controller
{
    public function index(Request $request, Event $event): Response
    {
        $this->authorize('view', $event);

        $competitions = $event->competitions()
            ->with([
                'sport:id,name',
                'format:id,name,slug',
                'rankings.rankable',
            ])
            ->when($request->filled('sport_id'), fn ($query) => $query->where('sport_id', $request->integer('sport_id')))
            ->orderBy('name')
            ->get()
            ->map(fn (Competition $competition) => [
                'id' => $competition->id,
                'name' => $competition->name,
                'sport' => $competition->sport?->only(['id', 'name']),
                'format' => $competition->format?->only(['id', 'name', 'slug']),
                'rankings' => $competition->rankings->map(fn ($ranking) => [
                    'position' => $ranking->position,
                    'name' => $ranking->rankable?->name,
                    'points' => $ranking->points,
                    'played' => $ranking->played,
                    'won' => $ranking->won,
                    'drawn' => $ranking->drawn,
                    'lost' => $ranking->lost,
                    'scored_for' => $ranking->scored_for,
                    'scored_against' => $ranking->scored_against,
                    'goal_difference' => $ranking->goalDifference(),
                ]),
            ]);

        return Inertia::render('Admin/Events/Rankings/Index', [
            'event' => $event->only(['id', 'name', 'slug']),
            'competitions' => $competitions,
            'sports' => $event->sports()->orderBy('name')->get(['id', 'name', 'slug']),
            'filters' => [
                'sport_id' => $request->string('sport_id')->toString(),
            ],
        ]);
    }
}