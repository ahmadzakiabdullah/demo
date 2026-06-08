<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Organization;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', AuditLog::class);

        $user = $request->user();

        $logs = AuditLog::query()
            ->with([
                'user:id,name,email',
                'organization:id,name,slug',
            ])
            ->when(! $user->isSystemOwner(), function ($query) use ($user) {
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
            ->paginate(15)
            ->withQueryString()
            ->through(fn (AuditLog $log) => [
                'id' => $log->id,
                'action' => $log->action,
                'auditable_type' => $log->auditableLabel(),
                'auditable_id' => $log->auditable_id,
                'organization' => $log->organization?->only(['id', 'name', 'slug']),
                'user' => $log->user?->only(['id', 'name', 'email']),
                'old_values' => $log->old_values,
                'new_values' => $log->new_values,
                'ip_address' => $log->ip_address,
                'created_at' => $log->created_at?->toDateTimeString(),
            ]);

        return Inertia::render('Admin/AuditLogs/Index', [
            'logs' => $logs,
            'filters' => [
                'search' => $request->string('search')->toString(),
                'action' => $request->string('action')->toString(),
                'organization_id' => $request->string('organization_id')->toString(),
            ],
            'actions' => ['created', 'updated', 'deleted'],
            'organizations' => $user->isSystemOwner()
                ? Organization::query()->orderBy('name')->get(['id', 'name', 'slug'])
                : $user->organizations()->orderBy('name')->get(['organizations.id', 'organizations.name', 'organizations.slug'])
                    ->map(fn (Organization $organization) => [
                        'id' => $organization->id,
                        'name' => $organization->name,
                        'slug' => $organization->slug,
                    ]),
        ]);
    }
}