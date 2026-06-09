<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreEventParticipantRequest;
use App\Http\Requests\Admin\UpdateEventParticipantRequest;
use App\Http\Requests\Api\V1\ImportEventParticipantsRequest;
use App\Http\Resources\Api\V1\EventParticipantResource;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Services\EventParticipantCsvImporter;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventParticipantController extends Controller
{
    public function index(Request $request, Event $event): JsonResponse
    {
        $this->authorize('view', $event);
        $this->authorize('viewAny', EventParticipant::class);

        $participants = $event->eventParticipants()
            ->withCount(['sportEntries', 'teams', 'athletes'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();
                $query->where(function ($builder) use ($search) {
                    $builder
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->string('type')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 25));

        return ApiResponse::paginated($participants, EventParticipantResource::class);
    }

    public function store(StoreEventParticipantRequest $request, Event $event): JsonResponse
    {
        $participant = EventParticipant::create([
            ...$request->validated(),
            'organization_id' => $event->organization_id,
            'event_id' => $event->id,
        ]);

        $participant->loadCount(['sportEntries', 'teams', 'athletes']);

        return ApiResponse::success(new EventParticipantResource($participant), 'Participant registered.', 201);
    }

    public function show(Event $event, EventParticipant $participant): JsonResponse
    {
        $this->authorize('view', $participant);
        abort_unless($participant->event_id === $event->id, 404);

        $participant->load([
            'branch:id,name,code',
            'sportEntries.sport:id,name,slug',
            'sportEntries.sportCategory:id,name',
            'sportEntries.sportDivision:id,name',
        ])->loadCount(['teams', 'athletes']);

        return ApiResponse::success(new EventParticipantResource($participant));
    }

    public function update(UpdateEventParticipantRequest $request, Event $event, EventParticipant $participant): JsonResponse
    {
        abort_unless($participant->event_id === $event->id, 404);

        $participant->update($request->validated());
        $participant->loadCount(['sportEntries', 'teams', 'athletes']);

        return ApiResponse::success(new EventParticipantResource($participant), 'Participant updated.');
    }

    public function destroy(Event $event, EventParticipant $participant): JsonResponse
    {
        $this->authorize('delete', $participant);
        abort_unless($participant->event_id === $event->id, 404);

        $participant->delete();

        return ApiResponse::success(message: 'Participant removed.');
    }

    public function import(
        ImportEventParticipantsRequest $request,
        Event $event,
        EventParticipantCsvImporter $importer,
    ): JsonResponse {
        $result = $importer->import($event, $request->file('file'));

        return ApiResponse::success($result, 'Participants imported.', 201);
    }
}