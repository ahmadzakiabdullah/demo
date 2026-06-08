<?php

namespace App\Http\Controllers;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $organization = $request->attributes->get('currentOrganization');

        $eventsQuery = Event::query();
        $usersQuery = User::query();

        if ($organization instanceof Organization) {
            $eventsQuery->where('organization_id', $organization->id);
            $usersQuery->whereHas(
                'organizations',
                fn ($query) => $query->where('organizations.id', $organization->id),
            );
        } elseif (! $user->isSystemOwner()) {
            $organizationIds = $user->organizations()->pluck('organizations.id');
            $assignedEventIds = $user->assignedEvents()->pluck('events.id');

            $eventsQuery->where(function ($query) use ($organizationIds, $assignedEventIds) {
                if ($organizationIds->isNotEmpty()) {
                    $query->whereIn('organization_id', $organizationIds);
                }

                if ($assignedEventIds->isNotEmpty()) {
                    $query->orWhereIn('id', $assignedEventIds);
                }

                if ($organizationIds->isEmpty() && $assignedEventIds->isEmpty()) {
                    $query->whereRaw('0 = 1');
                }
            });

            if ($organizationIds->isNotEmpty()) {
                $usersQuery->whereHas(
                    'organizations',
                    fn ($query) => $query->whereIn('organizations.id', $organizationIds),
                );
            }
        }

        $stats = [
            'organizations_count' => $organization instanceof Organization
                ? 1
                : ($user->isSystemOwner()
                    ? Organization::query()->count()
                    : $user->organizations()->count()),
            'events_count' => (clone $eventsQuery)->count(),
            'active_events_count' => (clone $eventsQuery)
                ->whereIn('status', [EventStatus::Active, EventStatus::Published])
                ->count(),
            'users_count' => (clone $usersQuery)->count(),
        ];

        $recentEvents = (clone $eventsQuery)
            ->with(['organization:id,name,slug'])
            ->orderByDesc('starts_at')
            ->orderBy('name')
            ->limit(5)
            ->get()
            ->map(fn (Event $event) => [
                'id' => $event->id,
                'name' => $event->name,
                'slug' => $event->slug,
                'status' => $event->status->value,
                'starts_at' => $event->starts_at?->toDateString(),
                'organization' => $event->organization?->only(['id', 'name', 'slug']),
            ])
            ->values()
            ->all();

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'recentEvents' => $recentEvents,
            'scope' => [
                'organization' => $organization?->only(['id', 'name', 'slug']),
                'is_global' => $organization === null && $user->isSystemOwner(),
            ],
        ]);
    }
}