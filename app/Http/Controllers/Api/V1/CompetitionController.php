<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\CompetitionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCompetitionGroupRequest;
use App\Http\Requests\Admin\StoreCompetitionRequest;
use App\Http\Requests\Admin\StoreFixtureRequest;
use App\Http\Requests\Admin\StoreMatchRequest;
use App\Http\Requests\Admin\UpdateCompetitionRequest;
use App\Http\Requests\Admin\UpdateMatchRequest;
use App\Http\Resources\Api\V1\CompetitionResource;
use App\Http\Resources\Api\V1\FixtureResource;
use App\Http\Resources\Api\V1\MatchResource;
use App\Models\Competition;
use App\Models\CompetitionFormat;
use App\Models\Event;
use App\Models\Fixture;
use App\Models\MatchGame;
use App\Support\ApiResponse;
use App\Support\DrawGenerator;
use App\Support\MatchScheduler;
use RuntimeException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CompetitionController extends Controller
{
    public function index(Request $request, Event $event): JsonResponse
    {
        $this->authorize('view', $event);
        $this->authorize('viewAny', Competition::class);

        $competitions = $event->competitions()
            ->with(['sport:id,name,slug', 'format:id,name,slug'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();
                $query->where('name', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->paginate($request->integer('per_page', 25));

        return ApiResponse::paginated($competitions, CompetitionResource::class);
    }

    public function store(StoreCompetitionRequest $request, Event $event): JsonResponse
    {
        $validated = $request->validated();
        $slug = $validated['slug'] ?? $this->uniqueSlug(
            $validated['name'],
            $event->id,
            (int) $validated['sport_id'],
        );

        $competition = Competition::query()->create([
            'organization_id' => $event->organization_id,
            'event_id' => $event->id,
            'sport_id' => $validated['sport_id'],
            'competition_format_id' => $validated['competition_format_id'],
            'name' => $validated['name'],
            'slug' => $slug,
            'status' => $validated['status'] ?? CompetitionStatus::Draft->value,
            'notes' => $validated['notes'] ?? null,
        ]);

        $competition->load(['sport:id,name,slug', 'format:id,name,slug']);

        return ApiResponse::success(new CompetitionResource($competition), 'Competition created.', 201);
    }

    public function show(Event $event, Competition $competition): JsonResponse
    {
        $this->ensureBelongsToEvent($event, $competition);
        $this->authorize('view', $competition);

        $competition->load([
            'sport:id,name,slug',
            'format:id,name,slug',
            'fixtures.matches.participants.participant',
            'fixtures.matches.officials.official:id,name',
            'fixtures.matches.venue:id,name',
            'fixtures.matches.facility:id,name',
        ]);

        return ApiResponse::success(new CompetitionResource($competition));
    }

    public function update(UpdateCompetitionRequest $request, Event $event, Competition $competition): JsonResponse
    {
        $this->ensureBelongsToEvent($event, $competition);

        $competition->update($request->validated());
        $competition->load(['sport:id,name,slug', 'format:id,name,slug']);

        return ApiResponse::success(new CompetitionResource($competition));
    }

    public function destroy(Event $event, Competition $competition): JsonResponse
    {
        $this->ensureBelongsToEvent($event, $competition);
        $this->authorize('delete', $competition);

        $competition->delete();

        return ApiResponse::success(message: 'Competition deleted.');
    }

    public function storeFixture(StoreFixtureRequest $request, Event $event, Competition $competition): JsonResponse
    {
        $this->ensureBelongsToEvent($event, $competition);

        $validated = $request->validated();

        $fixture = Fixture::query()->create([
            'competition_id' => $competition->id,
            'group_id' => $validated['group_id'] ?? null,
            'name' => $validated['name'],
            'round' => $validated['round'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return ApiResponse::success(new FixtureResource($fixture), 'Fixture created.', 201);
    }

    public function storeMatch(StoreMatchRequest $request, Event $event, Competition $competition, Fixture $fixture): JsonResponse
    {
        $this->ensureBelongsToEvent($event, $competition);
        $this->ensureFixtureBelongsToCompetition($competition, $fixture);

        $match = app(MatchScheduler::class)->create($fixture, $request->validated());
        $match->load(['participants.participant', 'officials.official:id,name', 'venue:id,name', 'facility:id,name']);

        return ApiResponse::success(new MatchResource($match), 'Match created.', 201);
    }

    public function updateMatch(UpdateMatchRequest $request, Event $event, Competition $competition, Fixture $fixture, MatchGame $matchGame): JsonResponse
    {
        $this->ensureBelongsToEvent($event, $competition);
        $this->ensureFixtureBelongsToCompetition($competition, $fixture);
        $this->ensureMatchBelongsToFixture($fixture, $matchGame);

        app(MatchScheduler::class)->update($matchGame, $request->validated());
        $matchGame->refresh()->load(['participants.participant', 'officials.official:id,name', 'venue:id,name', 'facility:id,name']);

        return ApiResponse::success(new MatchResource($matchGame));
    }

    public function generateDraw(Event $event, Competition $competition): JsonResponse
    {
        $this->ensureBelongsToEvent($event, $competition);
        $this->authorize('manageSchedule', $competition);

        try {
            $stats = app(DrawGenerator::class)->generate($competition);
        } catch (RuntimeException $exception) {
            return ApiResponse::success(['error' => $exception->getMessage()], $exception->getMessage(), 422);
        }

        $competition->load([
            'fixtures.matches.participants.participant',
            'fixtures.matches.result',
        ]);

        return ApiResponse::success([
            'stats' => $stats,
            'competition' => new CompetitionResource($competition),
        ], 'Draw generated.');
    }

    public function generateKnockoutPhase(Event $event, Competition $competition): JsonResponse
    {
        $this->ensureBelongsToEvent($event, $competition);
        $this->authorize('manageSchedule', $competition);

        try {
            $stats = app(DrawGenerator::class)->generateKnockoutPhase($competition);
        } catch (RuntimeException $exception) {
            return ApiResponse::success(['error' => $exception->getMessage()], $exception->getMessage(), 422);
        }

        return ApiResponse::success(['stats' => $stats], 'Knockout phase generated.');
    }

    public function bracket(Event $event, Competition $competition): JsonResponse
    {
        $this->ensureBelongsToEvent($event, $competition);
        $this->authorize('view', $competition);

        $competition->load([
            'fixtures' => fn ($query) => $query
                ->with(['matches.participants.participant', 'matches.result'])
                ->orderBy('sort_order'),
        ]);

        $rounds = $competition->fixtures->map(fn ($fixture) => [
            'round' => $fixture->round ?? $fixture->name,
            'matches' => $fixture->matches->map(fn ($match) => [
                'id' => $match->id,
                'participants' => $match->participants->map(fn ($participant) => [
                    'side' => $participant->side->value,
                    'name' => $participant->participant?->name,
                ]),
                'result' => $match->result?->data,
            ]),
        ]);

        return ApiResponse::success($rounds);
    }

    public function formats(): JsonResponse
    {
        $formats = CompetitionFormat::query()->orderBy('sort_order')->get();

        return ApiResponse::success($formats->map(fn ($format) => $format->only(['id', 'name', 'slug', 'description'])));
    }

    private function ensureBelongsToEvent(Event $event, Competition $competition): void
    {
        abort_unless($competition->event_id === $event->id, 404);
    }

    private function ensureFixtureBelongsToCompetition(Competition $competition, Fixture $fixture): void
    {
        abort_unless($fixture->competition_id === $competition->id, 404);
    }

    private function ensureMatchBelongsToFixture(Fixture $fixture, MatchGame $match): void
    {
        abort_unless($match->fixture_id === $fixture->id, 404);
    }

    private function uniqueSlug(string $name, int $eventId, int $sportId): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 1;

        while (Competition::withTrashed()
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