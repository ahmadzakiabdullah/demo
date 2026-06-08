<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AuditLogResource;
use App\Models\AuditLog;
use App\Models\Organization;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', AuditLog::class);

        $user = $request->user();
        $organization = $request->attributes->get('currentOrganization');

        $logs = AuditLog::query()
            ->with([
                'user:id,name,email',
                'organization:id,name,slug',
            ])
            ->when($organization instanceof Organization, fn ($query) => $query->where('organization_id', $organization->id))
            ->when(! $user->isSystemOwner() && ! $organization, function ($query) use ($user) {
                $organizationIds = $user->organizations()->pluck('organizations.id');

                $query->whereIn('organization_id', $organizationIds);
            })
            ->when($request->filled('organization_id') && $user->isSystemOwner(), function ($query) use ($request) {
                $query->where('organization_id', $request->integer('organization_id'));
            })
            ->when($request->filled('action'), fn ($query) => $query->where('action', $request->string('action')))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(function ($builder) use ($search) {
                    $builder
                        ->where('auditable_type', 'like', "%{$search}%")
                        ->orWhere('auditable_id', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 25));

        return ApiResponse::paginated($logs, AuditLogResource::class);
    }
}