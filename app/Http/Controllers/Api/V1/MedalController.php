<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Support\ApiResponse;
use App\Support\MedalTallyAggregator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MedalController extends Controller
{
    public function index(Request $request, Event $event): JsonResponse
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

        return ApiResponse::success([
            'medals' => $medals,
            'tally' => $aggregated,
        ]);
    }
}