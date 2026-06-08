<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreOrganizationRequest;
use App\Http\Requests\Admin\UpdateOrganizationRequest;
use App\Http\Resources\Api\V1\BranchResource;
use App\Http\Resources\Api\V1\OrganizationResource;
use App\Models\Organization;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OrganizationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Organization::class);

        $user = $request->user();

        $organizations = Organization::query()
            ->withCount('branches')
            ->when(! $user->isSystemOwner(), function ($query) use ($user) {
                $query->whereIn('id', $user->organizations()->pluck('organizations.id'));
            })
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

        return ApiResponse::paginated($organizations, OrganizationResource::class);
    }

    public function store(StoreOrganizationRequest $request): JsonResponse
    {
        $slug = $request->validated('slug') ?: $this->uniqueSlug($request->validated('name'));

        $organization = Organization::create([
            ...$request->safe()->only(['name', 'type', 'timezone', 'locale', 'status']),
            'slug' => $slug,
        ]);

        return ApiResponse::success(
            new OrganizationResource($organization),
            'Organization created.',
            201,
        );
    }

    public function show(Organization $organization): JsonResponse
    {
        $this->authorize('view', $organization);

        $organization->loadCount('branches');

        return ApiResponse::success(new OrganizationResource($organization));
    }

    public function update(UpdateOrganizationRequest $request, Organization $organization): JsonResponse
    {
        $organization->update($request->validated());

        return ApiResponse::success(
            new OrganizationResource($organization->fresh()->loadCount('branches')),
            'Organization updated.',
        );
    }

    public function destroy(Organization $organization): JsonResponse
    {
        $this->authorize('delete', $organization);

        $organization->delete();

        return ApiResponse::success(message: 'Organization deleted.');
    }

    public function branches(Organization $organization): JsonResponse
    {
        $this->authorize('view', $organization);

        $branches = $organization->branches()->orderBy('name')->get();

        return ApiResponse::success(BranchResource::collection($branches));
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 1;

        while (Organization::withTrashed()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}