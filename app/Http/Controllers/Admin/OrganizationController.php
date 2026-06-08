<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrganizationStatus;
use App\Enums\OrganizationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBranchRequest;
use App\Http\Requests\Admin\StoreOrganizationRequest;
use App\Http\Requests\Admin\UpdateOrganizationRequest;
use App\Models\Branch;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Organization::class);

        $organizations = Organization::query()
            ->withCount('branches')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(function ($builder) use ($search) {
                    $builder
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->string('type')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Organization $organization) => [
                'id' => $organization->id,
                'name' => $organization->name,
                'slug' => $organization->slug,
                'type' => $organization->type->value,
                'status' => $organization->status->value,
                'timezone' => $organization->timezone,
                'branches_count' => $organization->branches_count,
                'created_at' => $organization->created_at?->toDateString(),
            ]);

        return Inertia::render('Admin/Organizations/Index', [
            'organizations' => $organizations,
            'filters' => [
                'search' => $request->string('search')->toString(),
                'type' => $request->string('type')->toString(),
                'status' => $request->string('status')->toString(),
            ],
            'types' => OrganizationType::values(),
            'statuses' => OrganizationStatus::values(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Organization::class);

        return Inertia::render('Admin/Organizations/Create', [
            'types' => OrganizationType::values(),
            'statuses' => OrganizationStatus::values(),
            'defaultTimezone' => 'Asia/Kuala_Lumpur',
            'defaultLocale' => 'en',
        ]);
    }

    public function store(StoreOrganizationRequest $request): RedirectResponse
    {
        $slug = $request->validated('slug') ?: $this->uniqueSlug($request->validated('name'));

        Organization::create([
            ...$request->safe()->only(['name', 'type', 'timezone', 'locale', 'status']),
            'slug' => $slug,
        ]);

        return redirect()->route('admin.organizations.index')
            ->with('success', 'Organization created successfully.');
    }

    public function edit(Organization $organization): Response
    {
        $this->authorize('update', $organization);

        $organization->load(['branches' => fn ($query) => $query->orderBy('name')]);

        return Inertia::render('Admin/Organizations/Edit', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'slug' => $organization->slug,
                'type' => $organization->type->value,
                'timezone' => $organization->timezone,
                'locale' => $organization->locale,
                'status' => $organization->status->value,
            ],
            'branches' => $organization->branches->map(fn (Branch $branch) => [
                'id' => $branch->id,
                'name' => $branch->name,
                'code' => $branch->code,
            ]),
            'types' => OrganizationType::values(),
            'statuses' => OrganizationStatus::values(),
        ]);
    }

    public function update(UpdateOrganizationRequest $request, Organization $organization): RedirectResponse
    {
        $organization->update($request->validated());

        return redirect()->route('admin.organizations.index')
            ->with('success', 'Organization updated successfully.');
    }

    public function destroy(Organization $organization): RedirectResponse
    {
        $this->authorize('delete', $organization);

        $organization->delete();

        return redirect()->route('admin.organizations.index')
            ->with('success', 'Organization deleted successfully.');
    }

    public function storeBranch(StoreBranchRequest $request, Organization $organization): RedirectResponse
    {
        $this->authorize('update', $organization);

        $organization->branches()->create($request->validated());

        return redirect()->route('admin.organizations.edit', $organization)
            ->with('success', 'Branch added successfully.');
    }

    public function destroyBranch(Organization $organization, Branch $branch): RedirectResponse
    {
        $this->authorize('update', $organization);

        if ($branch->organization_id !== $organization->id) {
            abort(404);
        }

        $branch->delete();

        return redirect()->route('admin.organizations.edit', $organization)
            ->with('success', 'Branch removed successfully.');
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