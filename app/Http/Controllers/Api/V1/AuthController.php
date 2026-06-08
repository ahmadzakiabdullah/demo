<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\Role;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::query()->where('email', $request->validated('email'))->first();

        if (! $user || ! Hash::check($request->validated('password'), $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken($request->validated('device_name'))->plainTextToken;

        return ApiResponse::success([
            'token' => $token,
            'user' => new UserResource($user->load(['systemRoles' => fn ($q) => $q->whereNull('organization_id')])),
            'organizations' => $this->organizationMemberships($user),
        ], 'Authenticated.');
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load(['systemRoles' => fn ($q) => $q->whereNull('organization_id')]);

        return ApiResponse::success([
            'user' => new UserResource($user),
            'organizations' => $this->organizationMemberships($user),
            'permissions' => $user->isSystemOwner()
                ? ['*']
                : $user->systemRoles()
                    ->with('permissions')
                    ->whereNull('organization_id')
                    ->get()
                    ->flatMap(fn ($role) => $role->permissions->pluck('slug'))
                    ->unique()
                    ->values()
                    ->all(),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return ApiResponse::success(message: 'Logged out.');
    }

    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();
        $deviceName = $user->currentAccessToken()?->name ?? 'api-client';

        $user->currentAccessToken()?->delete();

        $token = $user->createToken($deviceName)->plainTextToken;

        return ApiResponse::success([
            'token' => $token,
            'user' => new UserResource($user->load(['systemRoles' => fn ($q) => $q->whereNull('organization_id')])),
        ], 'Token refreshed.');
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function organizationMemberships(User $user): array
    {
        return $user->organizations()
            ->get()
            ->map(function ($organization) {
                $role = Role::query()->find($organization->pivot->role_id);

                return [
                    'id' => $organization->id,
                    'name' => $organization->name,
                    'slug' => $organization->slug,
                    'role' => $role?->slug,
                    'status' => $organization->pivot->status,
                ];
            })
            ->values()
            ->all();
    }
}