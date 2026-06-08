<?php

namespace Tests\Feature\Api\V1;

use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_owner_can_list_users(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->count(2)->create();

        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/users')
            ->assertOk()
            ->assertJsonPath('meta.total', 3);
    }

    public function test_org_admin_can_list_users_in_their_organization(): void
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

        $member = User::withoutEvents(fn () => User::factory()->create());
        $member->organizations()->attach($organization->id, [
            'role_id' => Role::query()->where('slug', Role::ATHLETE)->value('id'),
            'status' => 'active',
        ]);

        User::factory()->create();

        Sanctum::actingAs($orgAdmin);

        $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->getJson('/api/v1/users')
            ->assertOk()
            ->assertJsonPath('meta.total', 2);
    }

    public function test_member_cannot_list_users(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/users')->assertForbidden();
    }

    public function test_system_owner_can_create_user(): void
    {
        $admin = User::factory()->admin()->create();

        Sanctum::actingAs($admin);

        $this->postJson('/api/v1/users', [
            'name' => 'API User',
            'email' => 'api-created@example.com',
            'password' => 'password123',
        ])
            ->assertCreated()
            ->assertJsonPath('data.email', 'api-created@example.com');

        $this->assertDatabaseHas('users', [
            'email' => 'api-created@example.com',
        ]);
    }

    public function test_user_cannot_delete_themselves(): void
    {
        $admin = User::factory()->admin()->create();

        Sanctum::actingAs($admin);

        $this->deleteJson("/api/v1/users/{$admin->id}")->assertForbidden();
    }
}