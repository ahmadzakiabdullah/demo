<?php

namespace Tests\Feature\Admin;

use App\Enums\FacilityType;
use App\Models\Facility;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VenueManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_access_venues(): void
    {
        $this->get(route('admin.venues.index'))
            ->assertRedirect(route('login'));
    }

    public function test_system_owner_can_view_venues_without_selected_organization(): void
    {
        $admin = User::factory()->admin()->create();
        $organization = Organization::withoutEvents(
            fn () => Organization::factory()->create(['name' => 'Alpha Sports']),
        );

        Venue::withoutEvents(fn () => Venue::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Main Arena',
        ]));

        $this->actingAs($admin)
            ->get(route('admin.venues.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Venues/Index')
                ->has('venues.data', 1)
                ->where('organization.id', $organization->id)
                ->where('venues.data.0.name', 'Main Arena'));
    }

    public function test_org_admin_can_view_venues_index(): void
    {
        $organization = Organization::withoutEvents(
            fn () => Organization::factory()->create(),
        );

        $orgAdminRoleId = Role::query()->where('slug', Role::ORG_ADMIN)->value('id');
        $orgAdmin = User::withoutEvents(fn () => User::factory()->create());
        $orgAdmin->organizations()->attach($organization->id, [
            'role_id' => $orgAdminRoleId,
            'status' => 'active',
        ]);

        Venue::withoutEvents(fn () => Venue::factory()->create([
            'organization_id' => $organization->id,
        ]));

        $this->actingAs($orgAdmin)
            ->get(route('admin.venues.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Venues/Index')
                ->has('venues.data', 1));
    }

    public function test_org_admin_can_create_venue(): void
    {
        $organization = Organization::withoutEvents(
            fn () => Organization::factory()->create(),
        );

        $orgAdminRoleId = Role::query()->where('slug', Role::ORG_ADMIN)->value('id');
        $orgAdmin = User::withoutEvents(fn () => User::factory()->create());
        $orgAdmin->organizations()->attach($organization->id, [
            'role_id' => $orgAdminRoleId,
            'status' => 'active',
        ]);

        $this->actingAs($orgAdmin)
            ->post(route('admin.venues.store'), [
                'name' => 'National Stadium',
                'address' => 'Kuala Lumpur',
                'capacity' => 50000,
                'timezone' => 'Asia/Kuala_Lumpur',
            ])
            ->assertRedirect();

        $venue = Venue::query()->where('name', 'National Stadium')->first();

        $this->assertNotNull($venue);
        $this->assertSame($organization->id, $venue->organization_id);
        $this->assertSame('national-stadium', $venue->slug);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'created',
            'auditable_type' => Venue::class,
            'auditable_id' => $venue->id,
        ]);
    }

    public function test_org_admin_can_manage_facilities(): void
    {
        $organization = Organization::withoutEvents(
            fn () => Organization::factory()->create(),
        );
        $venue = Venue::withoutEvents(fn () => Venue::factory()->create([
            'organization_id' => $organization->id,
        ]));

        $orgAdminRoleId = Role::query()->where('slug', Role::ORG_ADMIN)->value('id');
        $orgAdmin = User::withoutEvents(fn () => User::factory()->create());
        $orgAdmin->organizations()->attach($organization->id, [
            'role_id' => $orgAdminRoleId,
            'status' => 'active',
        ]);

        $this->actingAs($orgAdmin)
            ->post(route('admin.venues.facilities.store', $venue), [
                'name' => 'Court 1',
                'type' => FacilityType::Court->value,
                'capacity' => 500,
            ])
            ->assertRedirect();

        $facility = Facility::query()->where('name', 'Court 1')->first();

        $this->assertNotNull($facility);
        $this->assertSame($venue->id, $facility->venue_id);

        $this->actingAs($orgAdmin)
            ->delete(route('admin.venues.facilities.destroy', [$venue, $facility]))
            ->assertRedirect();

        $this->assertDatabaseMissing('facilities', [
            'id' => $facility->id,
        ]);
    }

    public function test_venue_slug_is_unique_within_organization(): void
    {
        $organization = Organization::withoutEvents(
            fn () => Organization::factory()->create(),
        );

        Venue::withoutEvents(fn () => Venue::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Arena',
            'slug' => 'arena',
        ]));

        $orgAdminRoleId = Role::query()->where('slug', Role::ORG_ADMIN)->value('id');
        $orgAdmin = User::withoutEvents(fn () => User::factory()->create());
        $orgAdmin->organizations()->attach($organization->id, [
            'role_id' => $orgAdminRoleId,
            'status' => 'active',
        ]);

        $this->actingAs($orgAdmin)
            ->post(route('admin.venues.store'), [
                'name' => 'Arena',
            ])
            ->assertRedirect();

        $this->assertSame(2, Venue::query()->where('organization_id', $organization->id)->count());
        $this->assertDatabaseHas('venues', [
            'organization_id' => $organization->id,
            'slug' => 'arena-1',
        ]);
    }

    public function test_member_cannot_create_venue(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.venues.store'), [
                'name' => 'Blocked Venue',
            ])
            ->assertForbidden();
    }
}