<?php

namespace Tests\Feature\Admin;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\EventType;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_access_dashboard(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_system_owner_sees_dashboard_stats(): void
    {
        $admin = User::factory()->admin()->create();
        $organization = Organization::withoutEvents(
            fn () => Organization::factory()->create(),
        );

        Event::withoutEvents(fn () => Event::factory()->create([
            'organization_id' => $organization->id,
            'status' => EventStatus::Published,
        ]));

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Dashboard')
                ->where('stats.organizations_count', 1)
                ->where('stats.events_count', 1)
                ->where('stats.active_events_count', 1)
                ->has('recentEvents', 1)
                ->where('scope.is_global', true));
    }

    public function test_org_admin_sees_scoped_dashboard_stats(): void
    {
        $organization = Organization::withoutEvents(
            fn () => Organization::factory()->create(),
        );
        $otherOrganization = Organization::withoutEvents(
            fn () => Organization::factory()->create(),
        );

        $orgAdminRoleId = Role::query()->where('slug', Role::ORG_ADMIN)->value('id');
        $orgAdmin = User::withoutEvents(fn () => User::factory()->create());
        $orgAdmin->organizations()->attach($organization->id, [
            'role_id' => $orgAdminRoleId,
            'status' => 'active',
        ]);

        Event::withoutEvents(fn () => Event::factory()->create([
            'organization_id' => $organization->id,
            'status' => EventStatus::Draft,
        ]));
        Event::withoutEvents(fn () => Event::factory()->create([
            'organization_id' => $otherOrganization->id,
            'status' => EventStatus::Published,
        ]));

        $this->actingAs($orgAdmin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Dashboard')
                ->where('stats.organizations_count', 1)
                ->where('stats.events_count', 1)
                ->where('scope.is_global', false)
                ->where('scope.organization.id', $organization->id));
    }
}