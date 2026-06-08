<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Support\MedalTallyAggregator;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MedalController extends Controller
{
    public function index(Request $request, Event $event): Response
    {
        $this->authorize('view', $event);

        $sportId = $request->filled('sport_id') ? $request->integer('sport_id') : null;

        $medals = $event->medals()
            ->with(['sport:id,name', 'competition:id,name', 'medalable'])
            ->when($sportId, fn ($query) => $query->where('sport_id', $sportId))
            ->orderBy('sport_id')
            ->orderBy('type')
            ->get()
            ->map(fn ($medal) => [
                'id' => $medal->id,
                'type' => $medal->type->value,
                'sport' => $medal->sport?->only(['id', 'name']),
                'competition' => $medal->competition?->only(['id', 'name']),
                'recipient' => $medal->medalable?->name,
            ]);

        $aggregated = app(MedalTallyAggregator::class)->aggregate($event, $sportId);

        return Inertia::render('Admin/Events/Medals/Index', [
            'event' => $event->only(['id', 'name', 'slug']),
            'medals' => $medals,
            'tally' => $aggregated['by_recipient'],
            'tallyByOrganization' => $aggregated['by_organization'],
            'tallyByCountry' => $aggregated['by_country'],
            'sports' => $event->sports()->orderBy('name')->get(['id', 'name', 'slug']),
            'filters' => [
                'sport_id' => $request->string('sport_id')->toString(),
            ],
        ]);
    }
}