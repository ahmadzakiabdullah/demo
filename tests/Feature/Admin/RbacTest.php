<?php

namespace Tests\Feature\Admin;

use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RbacTest extends TestCase
{
    use RefreshDatabase;

    public function test_roles_and_permissions_are_seeded_by_migration(): void
    {
        $this->assertDatabaseHas('roles', [
            'slug' => Role::SYSTEM_OWNER,
            'organization_id' => null,
        ]);

        $this->assertDatabaseHas('permissions', [
            'slug' => Permissions::slug('users', 'view'),
        ]);
    }

    public function test_system_owner_has_all_permissions(): void
    {
        $owner = User::factory()->admin()->create();

        $this->assertTrue($owner->hasPermission(Permissions::slug('organizations', 'delete')));
        $this->assertTrue($owner->hasPermission(Permissions::slug('users', 'manage')));
    }

    public function test_org_admin_has_organization_scoped_permissions(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();

        $orgAdminRoleId = Role::query()
            ->where('slug', Role::ORG_ADMIN)
            ->whereNull('organization_id')
            ->value('id');

        $user->organizations()->attach($organization->id, [
            'role_id' => $orgAdminRoleId,
            'status' => 'active',
        ]);

        $this->assertTrue($user->hasPermission(Permissions::slug('users', 'view'), $organization));
        $this->assertFalse($user->hasPermission(Permissions::slug('organizations', 'create')));
    }

    public function test_member_user_has_no_system_permissions(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($user->hasPermission(Permissions::slug('users', 'view')));
        $this->assertFalse($user->isSystemOwner());
    }

    public function test_legacy_admin_users_are_migrated_to_system_owner(): void
    {
        $user = User::factory()->create();
        $systemOwnerRoleId = Role::query()
            ->where('slug', Role::SYSTEM_OWNER)
            ->value('id');

        $user->systemRoles()->attach($systemOwnerRoleId);

        $this->assertTrue($user->fresh()->isSystemOwner());
    }
}