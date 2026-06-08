<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\RegistrationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAthleteRequest;
use App\Http\Requests\Admin\UpdateAthleteRequest;
use App\Http\Resources\Api\V1\AthleteResource;
use App\Models\Athlete;
use App\Models\Event;
use App\Models\Registration;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AthleteController extends Controller
{
    public function index(Request $request, Event $event): JsonResponse
    {
        $this->authorize('view', $event);
        $this->authorize('viewAny', Athlete::class);

        $athletes = Athlete::query()
            ->where('organization_id', $event->organization_id)
            ->whereHas('registrations', fn ($query) => $query->where('event_id', $event->id))
            ->with([
                'registrations' => fn ($query) => $query
                    ->where('event_id', $event->id)
                    ->with(['sport', 'sportCategory', 'sportDivision']),
            ])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();
                $query->where(function ($builder) use ($search) {
                    $builder
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('id_number', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), function ($query) use ($request, $event) {
                $query->whereHas('registrations', fn ($registrationQuery) => $registrationQuery
                    ->where('event_id', $event->id)
                    ->where('status', $request->string('status')->toString()));
            })
            ->orderBy('name')
            ->paginate($request->integer('per_page', 25));

        return ApiResponse::paginated($athletes, AthleteResource::class);
    }

    public function store(StoreAthleteRequest $request, Event $event): JsonResponse
    {
        $validated = $request->validated();

        $athlete = $validated['existing_athlete_id'] ?? null
            ? Athlete::query()->findOrFail($validated['existing_athlete_id'])
            : Athlete::create([
                'organization_id' => $event->organization_id,
                'user_id' => $validated['user_id'] ?? null,
                'name' => $validated['name'],
                'dob' => $validated['dob'] ?? null,
                'gender' => $validated['gender'] ?? null,
                'nationality' => $validated['nationality'] ?? null,
                'id_number' => $validated['id_number'] ?? null,
                'medical_clearance' => $validated['medical_clearance'] ?? false,
            ]);

        $registration = Registration::create([
            'event_id' => $event->id,
            'sport_id' => $validated['sport_id'],
            'registrable_type' => Athlete::class,
            'registrable_id' => $athlete->id,
            'sport_category_id' => $validated['sport_category_id'] ?? null,
            'sport_division_id' => $validated['sport_division_id'] ?? null,
            'status' => RegistrationStatus::Draft,
            'notes' => $validated['notes'] ?? null,
        ]);

        $athlete->load([
            'registrations' => fn ($query) => $query
                ->where('event_id', $event->id)
                ->with(['sport', 'sportCategory', 'sportDivision']),
        ]);

        return ApiResponse::success(new AthleteResource($athlete), 'Athlete registered.', 201);
    }

    public function show(Event $event, Athlete $athlete): JsonResponse
    {
        $this->authorize('view', $athlete);
        abort_unless($athlete->organization_id === $event->organization_id, 404);

        $athlete->load([
            'registrations' => fn ($query) => $query
                ->where('event_id', $event->id)
                ->with(['sport', 'sportCategory', 'sportDivision']),
        ]);

        $athlete->setRelation(
            'history',
            $athlete->registrations()
                ->with(['event:id,name,slug', 'sport:id,name'])
                ->where('event_id', '!=', $event->id)
                ->orderByDesc('created_at')
                ->get(),
        );

        abort_unless($athlete->registrations->isNotEmpty(), 404);

        return ApiResponse::success(new AthleteResource($athlete));
    }

    public function update(UpdateAthleteRequest $request, Event $event, Athlete $athlete): JsonResponse
    {
        abort_unless($athlete->organization_id === $event->organization_id, 404);

        $athlete->update($request->validated());

        $athlete->load([
            'registrations' => fn ($query) => $query
                ->where('event_id', $event->id)
                ->with(['sport', 'sportCategory', 'sportDivision']),
        ]);

        return ApiResponse::success(new AthleteResource($athlete), 'Athlete updated.');
    }

    public function destroy(Event $event, Athlete $athlete): JsonResponse
    {
        $this->authorize('delete', $athlete);
        abort_unless($athlete->organization_id === $event->organization_id, 404);

        $athlete->delete();

        return ApiResponse::success(message: 'Athlete deleted.');
    }
}