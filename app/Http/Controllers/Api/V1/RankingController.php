<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Models\Event;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RankingController extends Controller
{
    public function index(Request $request, Event $event): JsonResponse
    {
        $this->authorize('view', $event);

        $competitions = $event->competitions()
            ->with(['rankings.rankable', 'sport:id,name'])
            ->when($request->filled('sport_id'), fn ($query) => $query->where('sport_id', $request->integer('sport_id')))
            ->orderBy('name')
            ->get()
            ->map(fn (Competition $competition) => [
                'id' => $competition->id,
                'name' => $competition->name,
                'sport' => $competition->sport?->only(['id', 'name']),
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

        return ApiResponse::success($competitions);
    }
}