<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\RegistrationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTeamAthleteRequest;
use App\Http\Requests\Admin\StoreTeamRequest;
use App\Http\Requests\Admin\UpdateTeamRequest;
use App\Http\Resources\Api\V1\TeamResource;
use App\Models\Athlete;
use App\Models\Event;
use App\Models\Registration;
use App\Models\Team;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TeamController extends Controller
{
    public function index(Request $request, Event $event): JsonResponse
    {
        $this->authorize('view', $event);
        $this->authorize('viewAny', Team::class);

        $teams = $event->teams()
            ->with([
                'sport:id,name,slug',
                'coach:id,name,email',
                'manager:id,name,email',
                'registrations' => fn ($query) => $query->where('event_id', $event->id),
            ])
            ->withCount('athletes')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();
                $query->where(function ($builder) use ($search) {
                    $builder
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('sport_id'), fn ($query) => $query->where('sport_id', $request->integer('sport_id')))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 25));

        return ApiResponse::paginated($teams, TeamResource::class);
    }

    public function store(StoreTeamRequest $request, Event $event): JsonResponse
    {
        $validated = $request->validated();
        $slug = $validated['slug'] ?? $this->uniqueSlug(
            $validated['name'],
            $event->id,
            (int) $validated['sport_id'],
        );

        $team = Team::create([
            'organization_id' => $event->organization_id,
            'event_participant_id' => $validated['event_participant_id'],
            'event_id' => $event->id,
            'sport_id' => $validated['sport_id'],
            'name' => $validated['name'],
            'slug' => $slug,
            'coach_user_id' => $validated['coach_user_id'] ?? null,
            'manager_user_id' => $validated['manager_user_id'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        Registration::create([
            'event_id' => $event->id,
            'sport_id' => $validated['sport_id'],
            'registrable_type' => Team::class,
            'registrable_id' => $team->id,
            'sport_category_id' => $validated['sport_category_id'] ?? null,
            'sport_division_id' => $validated['sport_division_id'] ?? null,
            'status' => RegistrationStatus::Draft,
            'notes' => $validated['notes'] ?? null,
        ]);

        $team->load(['sport', 'coach', 'manager', 'registrations']);

        return ApiResponse::success(new TeamResource($team), 'Team registered.', 201);
    }

    public function show(Event $event, Team $team): JsonResponse
    {
        $this->authorize('view', $team);
        abort_unless($team->event_id === $event->id, 404);

        $team->load([
            'sport',
            'coach:id,name,email',
            'manager:id,name,email',
            'athletes',
            'registrations' => fn ($query) => $query
                ->where('event_id', $event->id)
                ->with(['sportCategory', 'sportDivision']),
        ]);

        return ApiResponse::success(new TeamResource($team));
    }

    public function update(UpdateTeamRequest $request, Event $event, Team $team): JsonResponse
    {
        abort_unless($team->event_id === $event->id, 404);

        $team->update($request->validated());
        $team->load(['sport', 'coach', 'manager', 'athletes', 'registrations']);

        return ApiResponse::success(new TeamResource($team), 'Team updated.');
    }

    public function destroy(Event $event, Team $team): JsonResponse
    {
        $this->authorize('delete', $team);
        abort_unless($team->event_id === $event->id, 404);

        $team->delete();

        return ApiResponse::success(message: 'Team deleted.');
    }

    public function storeAthlete(StoreTeamAthleteRequest $request, Event $event, Team $team): JsonResponse
    {
        abort_unless($team->event_id === $event->id, 404);

        $validated = $request->validated();

        $team->athletes()->attach($validated['athlete_id'], [
            'role' => $validated['role'],
            'jersey_number' => $validated['jersey_number'] ?? null,
        ]);

        $team->load('athletes');

        return ApiResponse::success(new TeamResource($team), 'Athlete added to roster.');
    }

    public function destroyAthlete(Event $event, Team $team, Athlete $athlete): JsonResponse
    {
        $this->authorize('manageRoster', $team);
        abort_unless($team->event_id === $event->id, 404);

        $team->athletes()->detach($athlete->id);
        $team->load('athletes');

        return ApiResponse::success(new TeamResource($team), 'Athlete removed from roster.');
    }

    private function uniqueSlug(string $name, int $eventId, int $sportId): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 1;

        while (Team::withTrashed()
            ->where('event_id', $eventId)
            ->where('sport_id', $sportId)
            ->where('slug', $slug)
            ->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}