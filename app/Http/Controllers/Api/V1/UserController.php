<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $user = $request->user();
        $organization = $request->attributes->get('currentOrganization');

        $users = User::query()
            ->with(['systemRoles' => fn ($q) => $q->whereNull('organization_id')])
            ->when($organization instanceof Organization, function ($query) use ($organization) {
                $query->whereHas('organizations', fn ($q) => $q->where('organizations.id', $organization->id));
            })
            ->when(! $user->isSystemOwner() && ! $organization, function ($query) use ($user) {
                $orgIds = $user->organizations()->pluck('organizations.id');
                $query->whereHas('organizations', fn ($q) => $q->whereIn('organizations.id', $orgIds));
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();
                $query->where(function ($builder) use ($search) {
                    $builder
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate($request->integer('per_page', 25));

        return ApiResponse::paginated($users, UserResource::class);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'system_role' => ['nullable', 'string', Rule::in(['', Role::SYSTEM_OWNER])],
        ]);

        $newUser = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'email_verified_at' => now(),
        ]);

        $newUser->syncSystemRole($validated['system_role'] ?? '');

        return ApiResponse::success(
            new UserResource($newUser->load(['systemRoles' => fn ($q) => $q->whereNull('organization_id')])),
            'User created.',
            201,
        );
    }

    public function show(User $user): JsonResponse
    {
        $this->authorize('view', $user);

        return ApiResponse::success(
            new UserResource($user->load(['systemRoles' => fn ($q) => $q->whereNull('organization_id')])),
        );
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $this->authorize('update', $user);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'system_role' => ['nullable', 'string', Rule::in(['', Role::SYSTEM_OWNER])],
        ]);

        if (isset($validated['name'])) {
            $user->name = $validated['name'];
        }

        if (isset($validated['email'])) {
            $user->email = $validated['email'];
        }

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        if (array_key_exists('system_role', $validated)) {
            $user->syncSystemRole($validated['system_role'] ?? '');
        }

        return ApiResponse::success(
            new UserResource($user->load(['systemRoles' => fn ($q) => $q->whereNull('organization_id')])),
            'User updated.',
        );
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($user->is($request->user())) {
            abort(403);
        }

        $this->authorize('delete', $user);

        $user->delete();

        return ApiResponse::success(message: 'User deleted.');
    }
}