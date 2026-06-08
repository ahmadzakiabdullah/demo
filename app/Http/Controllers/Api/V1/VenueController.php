<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreFacilityRequest;
use App\Http\Requests\Admin\StoreVenueRequest;
use App\Http\Requests\Admin\UpdateVenueRequest;
use App\Http\Resources\Api\V1\FacilityResource;
use App\Http\Resources\Api\V1\VenueResource;
use App\Models\Facility;
use App\Models\Organization;
use App\Models\Venue;
use App\Support\ApiResponse;
use App\Support\OrganizationContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class VenueController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Venue::class);

        $organization = $this->resolveOrganization($request);

        $venues = Venue::query()
            ->where('organization_id', $organization->id)
            ->withCount('facilities')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();
                $query->where(function ($builder) use ($search) {
                    $builder
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate($request->integer('per_page', 25));

        return ApiResponse::paginated($venues, VenueResource::class);
    }

    public function store(StoreVenueRequest $request): JsonResponse
    {
        $organization = $this->resolveOrganization($request);
        $validated = $request->validated();
        $slug = $validated['slug'] ?? $this->uniqueSlug($validated['name'], $organization->id);

        $venue = Venue::create([
            'organization_id' => $organization->id,
            'name' => $validated['name'],
            'slug' => $slug,
            'address' => $validated['address'] ?? null,
            'capacity' => $validated['capacity'] ?? null,
            'timezone' => $validated['timezone'] ?? 'UTC',
            'notes' => $validated['notes'] ?? null,
        ]);

        $venue->load('facilities');

        return ApiResponse::success(new VenueResource($venue), 'Venue created.', 201);
    }

    public function show(Request $request, Venue $venue): JsonResponse
    {
        $this->authorize('view', $venue);
        $this->ensureVenueInOrganization($request, $venue);

        $venue->load('facilities');

        return ApiResponse::success(new VenueResource($venue));
    }

    public function update(UpdateVenueRequest $request, Venue $venue): JsonResponse
    {
        $this->ensureVenueInOrganization($request, $venue);

        $validated = $request->validated();
        $slug = $validated['slug'] ?? $this->uniqueSlug(
            $validated['name'],
            $venue->organization_id,
            $venue->id,
        );

        $venue->update([
            ...$validated,
            'slug' => $slug,
        ]);

        $venue->load('facilities');

        return ApiResponse::success(new VenueResource($venue), 'Venue updated.');
    }

    public function destroy(Request $request, Venue $venue): JsonResponse
    {
        $this->authorize('delete', $venue);
        $this->ensureVenueInOrganization($request, $venue);

        $venue->delete();

        return ApiResponse::success(message: 'Venue deleted.');
    }

    public function storeFacility(StoreFacilityRequest $request, Venue $venue): JsonResponse
    {
        $this->ensureVenueInOrganization($request, $venue);

        $validated = $request->validated();
        $slug = $validated['slug'] ?? $this->uniqueFacilitySlug($validated['name'], $venue->id);
        $sortOrder = $validated['sort_order']
            ?? ((int) $venue->facilities()->max('sort_order')) + 1;

        $facility = Facility::create([
            'venue_id' => $venue->id,
            'name' => $validated['name'],
            'slug' => $slug,
            'type' => $validated['type'],
            'capacity' => $validated['capacity'] ?? null,
            'sort_order' => $sortOrder,
        ]);

        return ApiResponse::success(new FacilityResource($facility), 'Facility created.', 201);
    }

    public function destroyFacility(Request $request, Venue $venue, Facility $facility): JsonResponse
    {
        $this->authorize('update', $venue);
        $this->ensureVenueInOrganization($request, $venue);
        abort_unless($facility->venue_id === $venue->id, 404);

        $facility->delete();

        return ApiResponse::success(message: 'Facility deleted.');
    }

    private function resolveOrganization(Request $request): Organization
    {
        return OrganizationContext::resolve($request);
    }

    private function ensureVenueInOrganization(Request $request, Venue $venue): void
    {
        $organization = $this->resolveOrganization($request);
        abort_unless($venue->organization_id === $organization->id, 404);
    }

    private function uniqueSlug(string $name, int $organizationId, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 1;

        while (Venue::withTrashed()
            ->where('organization_id', $organizationId)
            ->where('slug', $slug)
            ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    private function uniqueFacilitySlug(string $name, int $venueId): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 1;

        while (Facility::query()
            ->where('venue_id', $venueId)
            ->where('slug', $slug)
            ->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}