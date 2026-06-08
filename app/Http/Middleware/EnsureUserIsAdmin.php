<?php

namespace App\Http\Middleware;

use App\Support\Permissions;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user?->isSystemOwner()
            && ! $user?->hasPermission(Permissions::slug('users', 'manage'))
            && ! $user?->hasPermission(Permissions::slug('organizations', 'manage'))) {
            abort(403);
        }

        return $next($request);
    }
}