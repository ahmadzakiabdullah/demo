<?php

namespace Tests\Feature\Api\V1;

use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrganizationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_owner_can_list_organizations(): void
    {
        $admin = User::factory()->admin()->create();
        Organization::withoutEvents(fn () => Organization::factory()->count(2)->create());

        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/organizations')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [['id', 'name', 'slug', 'type', 'status']],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ])
            ->assertJsonPath('meta.total', 2);
    }

    public function test_org_admin_cannot_list_all_organizations(): void
    {
        $organization = Organization::withoutEvents(
            fn () => Organization::factory()->create(),
        );
        Organization::withoutEvents(
            fn () => Organization::factory()->create(),
        );

        $orgAdminRoleId = Role::query()->where('slug', Role::ORG_ADMIN)->value('id');
        $orgAdmin = User::withoutEvents(fn () => User::factory()->create());
        $orgAdmin->organizations()->attach($organization->id, [
            'role_id' => $orgAdminRoleId,
            'status' => 'active',
        ]);

        Sanctum::actingAs($orgAdmin);

        $this->getJson('/api/v1/organizations')->assertForbidden();
    }

    public function test_system_owner_can_create_organization(): void
    {
        $admin = User::factory()->admin()->create();

        Sanctum::actingAs($admin);

        $this->postJson('/api/v1/organizations', [
            'name' => 'New Federation',
            'type' => 'federation',
            'timezone' => 'Asia/Kuala_Lumpur',
            'locale' => 'en',
            'status' => 'active',
        ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'New Federation')
            ->assertJsonPath('data.slug', 'new-federation');

        $this->assertDatabaseHas('organizations', [
            'name' => 'New Federation',
            'slug' => 'new-federation',
        ]);
    }

    public function test_org_member_can_view_their_organization(): void
    {
        $organization = Organization::withoutEvents(
            fn () => Organization::factory()->create(['name' => 'Pilot Org']),
        );

        $orgAdminRoleId = Role::query()->where('slug', Role::ORG_ADMIN)->value('id');
        $orgAdmin = User::withoutEvents(fn () => User::factory()->create());
        $orgAdmin->organizations()->attach($organization->id, [
            'role_id' => $orgAdminRoleId,
            'status' => 'active',
        ]);

        Sanctum::actingAs($orgAdmin);

        $this->getJson("/api/v1/organizations/{$organization->id}")
            ->assertOk()
            ->assertJsonPath('data.name', 'Pilot Org');
    }

    public function test_system_owner_can_list_organization_branches(): void
    {
        $admin = User::factory()->admin()->create();
        $organization = Organization::withoutEvents(
            fn () => Organization::factory()->create(),
        );
        $organization->branches()->create([
            'name' => 'Main Campus',
            'code' => 'MAIN',
        ]);

        Sanctum::actingAs($admin);

        $this->getJson("/api/v1/organizations/{$organization->id}/branches")
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Main Campus');
    }
}