<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OrganizationSwitchController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $rawOrganizationId = $request->input('organization_id');

        if ($rawOrganizationId === null || $rawOrganizationId === '') {
            $request->session()->forget('current_organization_id');

            return back();
        }

        $validated = $request->validate([
            'organization_id' => ['required', 'integer', 'exists:organizations,id'],
        ]);

        $organizationId = $validated['organization_id'];

        if (! $user->isAdmin()
            && ! $user->organizations()->where('organizations.id', $organizationId)->exists()) {
            abort(403);
        }

        $request->session()->put('current_organization_id', $organizationId);

        return back();
    }
}