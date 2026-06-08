<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use App\Support\Permissions;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'system_role' => $user->primarySystemRole()?->only(['slug', 'name']),
                    'is_admin' => $user->isAdmin(),
                    'can_view_audit_logs' => $user->canViewAuditLogs(),
                    'can_view_events' => $user->canViewEvents(),
                    'can_view_sports' => $user->canViewSports(),
                    'can_view_athletes' => $user->canViewAthletes(),
                    'can_view_teams' => $user->canViewTeams(),
                    'can_view_officials' => $user->canViewOfficials(),
                    'can_view_venues' => $user->canViewVenues(),
                    'can_view_competitions' => $user->canViewCompetitions(),
                    'can_view_results' => $user->canViewResults(),
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
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
            ],
            'currentOrganization' => fn () => $this->currentOrganization($request),
            'organizations' => fn () => $this->userOrganizations($user),
        ];
    }

    /**
     * @return list<array{id: int, name: string, slug: string}>
     */
    private function userOrganizations(?\App\Models\User $user): array
    {
        if ($user === null) {
            return [];
        }

        if ($user->isSystemOwner()) {
            return Organization::query()
                ->orderBy('name')
                ->get(['id', 'name', 'slug'])
                ->map(fn (Organization $organization) => $organization->only(['id', 'name', 'slug']))
                ->values()
                ->all();
        }

        return $user->organizations()
            ->orderBy('organizations.name')
            ->get(['organizations.id', 'organizations.name', 'organizations.slug'])
            ->map(fn (Organization $organization) => [
                'id' => $organization->id,
                'name' => $organization->name,
                'slug' => $organization->slug,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function currentOrganization(Request $request): ?array
    {
        $organization = $request->attributes->get('currentOrganization');

        if (! $organization instanceof Organization) {
            return null;
        }

        return [
            'id' => $organization->id,
            'name' => $organization->name,
            'slug' => $organization->slug,
        ];
    }
}