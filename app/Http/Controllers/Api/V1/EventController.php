<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreEventRequest;
use App\Http\Requests\Admin\UpdateEventRequest;
use App\Http\Requests\Api\V1\UpdateEventStatusRequest;
use App\Http\Resources\Api\V1\EventResource;
use App\Models\Event;
use App\Models\Organization;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EventController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Event::class);

        $user = $request->user();
        $organization = $request->attributes->get('currentOrganization');

        $events = Event::query()
            ->with(['organization:id,name,slug', 'eventType:id,name,slug', 'eventCategory:id,name,slug'])
            ->when(! $user->isSystemOwner(), fn ($query) => $this->scopeToAccessibleEvents($query, $user))
            ->when($organization instanceof Organization, fn ($query) => $query->where('organization_id', $organization->id))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(function ($builder) use ($search) {
                    $builder
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('organization_id') && $user->isSystemOwner(), function ($query) use ($request) {
                $query->where('organization_id', $request->integer('organization_id'));
            })
            ->orderByDesc('starts_at')
            ->orderBy('name')
            ->paginate($request->integer('per_page', 25));

        return ApiResponse::paginated($events, EventResource::class);
    }

    public function store(StoreEventRequest $request): JsonResponse
    {
        $slug = $request->validated('slug') ?: $this->uniqueSlug(
            $request->validated('name'),
            $request->integer('organization_id'),
        );

        $event = Event::create([
            ...$request->safe()->only([
                'organization_id',
                'event_type_id',
                'event_category_id',
                'name',
                'location',
                'description',
                'starts_at',
                'ends_at',
            ]),
            'slug' => $slug,
            'status' => $request->validated('status'),
        ]);

        $event->load(['organization:id,name,slug', 'eventType:id,name,slug', 'eventCategory:id,name,slug']);

        return ApiResponse::success(
            new EventResource($event),
            'Event created.',
            201,
        );
    }

    public function show(Event $event): JsonResponse
    {
        $this->authorize('view', $event);

        $event->load([
            'organization:id,name,slug',
            'eventType:id,name,slug',
            'eventCategory:id,name,slug',
            'assignees:id,name,email',
        ]);

        return ApiResponse::success(new EventResource($event));
    }

    public function update(UpdateEventRequest $request, Event $event): JsonResponse
    {
        $event->update($request->validated());

        $event->load(['organization:id,name,slug', 'eventType:id,name,slug', 'eventCategory:id,name,slug']);

        return ApiResponse::success(new EventResource($event), 'Event updated.');
    }

    public function destroy(Event $event): JsonResponse
    {
        $this->authorize('delete', $event);

        $event->delete();

        return ApiResponse::success(message: 'Event deleted.');
    }

    public function updateStatus(UpdateEventStatusRequest $request, Event $event): JsonResponse
    {
        $event->update(['status' => $request->validated('status')]);

        $event->load(['organization:id,name,slug', 'eventType:id,name,slug', 'eventCategory:id,name,slug']);

        return ApiResponse::success(new EventResource($event), 'Event status updated.');
    }

    private function scopeToAccessibleEvents($query, User $user): void
    {
        $organizationIds = $user->organizations()->pluck('organizations.id');
        $assignedEventIds = $user->assignedEvents()->pluck('events.id');

        $query->where(function ($builder) use ($organizationIds, $assignedEventIds) {
            if ($organizationIds->isNotEmpty()) {
                $builder->whereIn('organization_id', $organizationIds);
            }

            if ($assignedEventIds->isNotEmpty()) {
                $builder->orWhereIn('id', $assignedEventIds);
            }

            if ($organizationIds->isEmpty() && $assignedEventIds->isEmpty()) {
                $builder->whereRaw('0 = 1');
            }
        });
    }

    private function uniqueSlug(string $name, int $organizationId): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 1;

        while (Event::withTrashed()
            ->where('organization_id', $organizationId)
            ->where('slug', $slug)
            ->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}