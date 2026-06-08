<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreFacilityRequest;
use App\Models\Facility;
use App\Models\Venue;
use App\Support\OrganizationContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FacilityController extends Controller
{
    public function store(StoreFacilityRequest $request, Venue $venue): RedirectResponse
    {
        $this->ensureVenueInOrganization($request, $venue);

        $validated = $request->validated();
        $slug = $validated['slug'] ?? $this->uniqueSlug($validated['name'], $venue->id);
        $sortOrder = $validated['sort_order']
            ?? ((int) $venue->facilities()->max('sort_order')) + 1;

        Facility::create([
            'venue_id' => $venue->id,
            'name' => $validated['name'],
            'slug' => $slug,
            'type' => $validated['type'],
            'capacity' => $validated['capacity'] ?? null,
            'sort_order' => $sortOrder,
        ]);

        return back()->with('success', 'Facility added successfully.');
    }

    public function destroy(Request $request, Venue $venue, Facility $facility): RedirectResponse
    {
        $this->authorize('update', $venue);
        $this->ensureVenueInOrganization($request, $venue);
        abort_unless($facility->venue_id === $venue->id, 404);

        $facility->delete();

        return back()->with('success', 'Facility removed successfully.');
    }

    private function ensureVenueInOrganization(Request $request, Venue $venue): void
    {
        $organization = OrganizationContext::resolve($request);

        abort_unless($venue->organization_id === $organization->id, 404);
    }

    private function uniqueSlug(string $name, int $venueId): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 1;

        while (Facility::query()
            ->where('venue_id', $venueId)
            ->where('slug', $slug)
            ->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}