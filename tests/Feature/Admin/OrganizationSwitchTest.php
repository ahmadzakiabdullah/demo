<?php

namespace Tests\Feature\Admin;

use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationSwitchTest extends TestCase
{
    use RefreshDatabase;

    public function test_org_admin_can_switch_to_their_organization(): void
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
            ->post(route('admin.organization.switch'), [
                'organization_id' => $organization->id,
            ])
            ->assertRedirect();

        $this->assertSame(
            $organization->id,
            session('current_organization_id'),
        );
    }

    public function test_org_admin_cannot_switch_to_foreign_organization(): void
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

        $this->actingAs($orgAdmin)
            ->post(route('admin.organization.switch'), [
                'organization_id' => $otherOrganization->id,
            ])
            ->assertForbidden();
    }

    public function test_system_owner_can_clear_organization_scope(): void
    {
        $organization = Organization::withoutEvents(
            fn () => Organization::factory()->create(),
        );
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('admin.organization.switch'), [
                'organization_id' => '',
            ])
            ->assertRedirect();

        $this->assertNull(session('current_organization_id'));
    }
}