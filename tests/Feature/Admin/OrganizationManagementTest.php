<?php

namespace Tests\Feature\Admin;

use App\Models\Branch;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_access_organizations(): void
    {
        $this->get(route('admin.organizations.index'))->assertRedirect(route('login'));
    }

    public function test_non_admin_users_cannot_access_organizations(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.organizations.index'))
            ->assertForbidden();
    }

    public function test_admin_can_view_organization_listing(): void
    {
        $admin = User::factory()->admin()->create();
        Organization::factory()->count(2)->create();

        $this->actingAs($admin)
            ->get(route('admin.organizations.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Organizations/Index')
                ->has('organizations.data', 2)
                ->has('types')
                ->has('statuses'));
    }

    public function test_admin_can_search_organizations(): void
    {
        $admin = User::factory()->admin()->create();
        Organization::factory()->create(['name' => 'UTeM Sports', 'slug' => 'utem-sports']);
        Organization::factory()->create(['name' => 'Other Club', 'slug' => 'other-club']);

        $this->actingAs($admin)
            ->get(route('admin.organizations.index', ['search' => 'utem']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('organizations.data', 1)
                ->where('organizations.data.0.slug', 'utem-sports'));
    }

    public function test_admin_can_create_organization_with_generated_slug(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.organizations.store'), [
                'name' => 'Melaka Athletics',
                'type' => 'club',
                'timezone' => 'Asia/Kuala_Lumpur',
                'locale' => 'en',
                'status' => 'active',
            ])
            ->assertRedirect(route('admin.organizations.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('organizations', [
            'name' => 'Melaka Athletics',
            'slug' => 'melaka-athletics',
            'type' => 'club',
            'status' => 'active',
        ]);
    }

    public function test_admin_can_update_organization(): void
    {
        $admin = User::factory()->admin()->create();
        $organization = Organization::factory()->create();

        $this->actingAs($admin)
            ->put(route('admin.organizations.update', $organization), [
                'name' => 'Updated Organization',
                'slug' => 'updated-org',
                'type' => 'school',
                'timezone' => 'Asia/Kuala_Lumpur',
                'locale' => 'en',
                'status' => 'suspended',
            ])
            ->assertRedirect(route('admin.organizations.index'));

        $this->assertDatabaseHas('organizations', [
            'id' => $organization->id,
            'name' => 'Updated Organization',
            'slug' => 'updated-org',
            'status' => 'suspended',
        ]);
    }

    public function test_admin_can_delete_organization(): void
    {
        $admin = User::factory()->admin()->create();
        $organization = Organization::factory()->create();

        $this->actingAs($admin)
            ->delete(route('admin.organizations.destroy', $organization))
            ->assertRedirect(route('admin.organizations.index'));

        $this->assertSoftDeleted('organizations', ['id' => $organization->id]);
    }

    public function test_admin_can_add_and_remove_branch(): void
    {
        $admin = User::factory()->admin()->create();
        $organization = Organization::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.organizations.branches.store', $organization), [
                'name' => 'Main Campus',
                'code' => 'MAIN',
            ])
            ->assertRedirect(route('admin.organizations.edit', $organization));

        $branch = Branch::query()->where('organization_id', $organization->id)->first();
        $this->assertNotNull($branch);
        $this->assertSame('Main Campus', $branch->name);

        $this->actingAs($admin)
            ->delete(route('admin.organizations.branches.destroy', [$organization, $branch]))
            ->assertRedirect(route('admin.organizations.edit', $organization));

        $this->assertDatabaseMissing('branches', ['id' => $branch->id]);
    }

    public function test_admin_cannot_remove_branch_from_another_organization(): void
    {
        $admin = User::factory()->admin()->create();
        $organization = Organization::factory()->create();
        $otherOrganization = Organization::factory()->create();
        $branch = Branch::factory()->create(['organization_id' => $otherOrganization->id]);

        $this->actingAs($admin)
            ->delete(route('admin.organizations.branches.destroy', [$organization, $branch]))
            ->assertNotFound();
    }
}