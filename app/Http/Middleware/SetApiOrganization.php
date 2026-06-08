<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetApiOrganization
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && $request->hasHeader('X-Organization-Id')) {
            $organizationId = (int) $request->header('X-Organization-Id');

            if ($request->user()->isSystemOwner()
                || $request->user()->organizations()->where('organizations.id', $organizationId)->exists()) {
                $organization = Organization::query()->find($organizationId);

                if ($organization) {
                    $request->attributes->set('currentOrganization', $organization);
                }
            }
        }

        return $next($request);
    }
}