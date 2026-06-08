<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSportRequest;
use App\Http\Requests\Admin\UpdateSportRequest;
use App\Http\Resources\Api\V1\SportResource;
use App\Models\Event;
use App\Models\Sport;
use App\Services\SportStructureService;
use App\Support\ApiResponse;
use App\Support\SportTemplates;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SportController extends Controller
{
    public function index(Request $request, Event $event): JsonResponse
    {
        $this->authorize('view', $event);
        $this->authorize('viewAny', Sport::class);

        $sports = $event->sports()
            ->withCount('disciplines')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();
                $query->where(function ($builder) use ($search) {
                    $builder
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate($request->integer('per_page', 25));

        return ApiResponse::paginated($sports, SportResource::class);
    }

    public function store(
        StoreSportRequest $request,
        Event $event,
        SportStructureService $structureService,
    ): JsonResponse {
        $template = $request->filled('template_slug')
            ? SportTemplates::find($request->string('template_slug')->toString())
            : null;

        $name = $request->validated('name') ?: $template['name'] ?? 'Sport';

        $sport = Sport::create([
            'event_id' => $event->id,
            'name' => $name,
            'slug' => $request->validated('slug') ?: $this->uniqueSlug($name, $event->id),
            'template_slug' => $request->validated('template_slug'),
            'status' => $request->validated('status'),
            'rules' => $request->validated('rules') ?? $template['rules'] ?? null,
        ]);

        if ($template !== null) {
            $structureService->applyTemplate($sport, $template['slug']);
        }

        $sport->load(['disciplines.categories.divisions']);

        return ApiResponse::success(new SportResource($sport), 'Sport created.', 201);
    }

    public function show(Event $event, Sport $sport): JsonResponse
    {
        $this->authorize('view', $sport);
        abort_unless($sport->event_id === $event->id, 404);

        $sport->load(['disciplines.categories.divisions']);

        return ApiResponse::success(new SportResource($sport));
    }

    public function update(UpdateSportRequest $request, Event $event, Sport $sport): JsonResponse
    {
        abort_unless($sport->event_id === $event->id, 404);

        $sport->update($request->validated());

        return ApiResponse::success(new SportResource($sport->fresh()), 'Sport updated.');
    }

    public function destroy(Event $event, Sport $sport): JsonResponse
    {
        $this->authorize('delete', $sport);
        abort_unless($sport->event_id === $event->id, 404);

        $sport->delete();

        return ApiResponse::success(message: 'Sport deleted.');
    }

    private function uniqueSlug(string $name, int $eventId): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 1;

        while (Sport::withTrashed()->where('event_id', $eventId)->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}