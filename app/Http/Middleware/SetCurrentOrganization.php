<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCurrentOrganization
{
    /**
     * Persist the active organization in session for future tenant-scoped routes.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && $request->hasHeader('X-Organization-Id')) {
            $organizationId = (int) $request->header('X-Organization-Id');

            if ($request->user()->isAdmin()
                || $request->user()->organizations()->where('organizations.id', $organizationId)->exists()) {
                $organization = Organization::query()->find($organizationId);

                if ($organization) {
                    $request->attributes->set('currentOrganization', $organization);
                }
            }
        }

        if ($request->user() && $request->has('organization_id')) {
            $organizationId = $request->integer('organization_id');

            if ($request->user()->isAdmin() || $request->user()->organizations()->where('organizations.id', $organizationId)->exists()) {
                if ($request->hasSession()) {
                    $request->session()->put('current_organization_id', $organizationId);
                }
            }
        }

        if ($request->user() && $request->hasSession() && $request->session()->has('current_organization_id')) {
            $organization = Organization::query()->find(
                (int) $request->session()->get('current_organization_id'),
            );

            if ($organization) {
                $request->attributes->set('currentOrganization', $organization);
            } else {
                $request->session()->forget('current_organization_id');
            }
        }

        if ($request->user()
            && ! $request->attributes->has('currentOrganization')
            && ! $request->user()->isAdmin()) {
            $organization = $request->user()
                ->organizations()
                ->orderBy('organizations.name')
                ->first();

            if ($organization) {
                $request->attributes->set('currentOrganization', $organization);

                if ($request->hasSession()) {
                    $request->session()->put('current_organization_id', $organization->id);
                }
            }
        }

        return $next($request);
    }
}