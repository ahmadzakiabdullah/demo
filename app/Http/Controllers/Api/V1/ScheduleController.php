<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\MatchResource;
use App\Models\Competition;
use App\Models\Event;
use App\Models\MatchGame;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ScheduleController extends Controller
{
    public function index(Request $request, Event $event): JsonResponse
    {
        $this->authorize('view', $event);
        $this->authorize('viewAny', Competition::class);

        $startDate = $request->filled('from')
            ? Carbon::parse($request->string('from')->toString())->startOfDay()
            : now()->startOfDay();

        $endDate = $request->filled('to')
            ? Carbon::parse($request->string('to')->toString())->endOfDay()
            : $startDate->copy()->addDays(6)->endOfDay();

        $matches = MatchGame::query()
            ->whereHas('fixture.competition', fn ($query) => $query->where('event_id', $event->id))
            ->whereNotNull('scheduled_at')
            ->whereBetween('scheduled_at', [$startDate, $endDate])
            ->with([
                'venue:id,name',
                'facility:id,name',
                'participants.participant',
                'fixture.competition.sport:id,name',
                'fixture:id,name,round,competition_id',
            ])
            ->when($request->filled('sport_id'), function ($query) use ($request) {
                $query->whereHas('fixture.competition', fn ($competition) => $competition
                    ->where('sport_id', $request->integer('sport_id')));
            })
            ->orderBy('scheduled_at')
            ->paginate($request->integer('per_page', 50));

        return ApiResponse::paginated($matches, MatchResource::class);
    }
}