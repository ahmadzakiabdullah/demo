<?php

namespace App\Support;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;

class OrganizationContext
{
    public static function resolve(Request $request): Organization
    {
        $organization = $request->attributes->get('currentOrganization');

        if ($organization instanceof Organization) {
            return $organization;
        }

        $user = $request->user();

        if ($user === null) {
            abort(403, 'No organization context.');
        }

        if ($request->filled('organization_id')) {
            $candidate = Organization::query()->find($request->integer('organization_id'));

            if ($candidate !== null && static::userCanAccess($user, $candidate)) {
                return static::remember($request, $candidate);
            }
        }

        if ($user->isSystemOwner()) {
            $candidate = Organization::query()->orderBy('name')->first();

            if ($candidate !== null) {
                return static::remember($request, $candidate);
            }
        } else {
            $candidate = $user->organizations()->orderBy('organizations.name')->first();

            if ($candidate !== null) {
                return static::remember($request, $candidate);
            }
        }

        abort(403, 'No organization context.');
    }

    private static function userCanAccess(User $user, Organization $organization): bool
    {
        return $user->isSystemOwner()
            || $user->organizations()->where('organizations.id', $organization->id)->exists();
    }

    private static function remember(Request $request, Organization $organization): Organization
    {
        $request->attributes->set('currentOrganization', $organization);

        if ($request->hasSession()) {
            $request->session()->put('current_organization_id', $organization->id);
        }

        return $organization;
    }
}