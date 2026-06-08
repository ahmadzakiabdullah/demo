<?php

namespace Tests\Feature\Api\V1;

use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_and_receive_token(): void
    {
        $user = User::factory()->create([
            'email' => 'api@example.com',
            'password' => 'password',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'api@example.com',
            'password' => 'password',
            'device_name' => 'phpunit',
        ]);

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'user' => ['id', 'name', 'email'],
                    'organizations',
                ],
                'message',
            ]);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'phpunit',
        ]);
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        User::factory()->create(['email' => 'api@example.com']);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'api@example.com',
            'password' => 'wrong-password',
            'device_name' => 'phpunit',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_authenticated_user_can_fetch_profile(): void
    {
        $user = User::factory()->admin()->create();

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.user.email', $user->email)
            ->assertJsonPath('data.permissions', ['*']);
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('phpunit')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/auth/logout')
            ->assertOk();

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'phpunit',
        ]);
    }

    public function test_authenticated_user_can_refresh_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('mobile-app')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/auth/refresh');

        $response
            ->assertOk()
            ->assertJsonStructure(['data' => ['token', 'user']]);

        $this->assertSame(1, $user->fresh()->tokens()->count());
        $this->assertNotSame($token, $response->json('data.token'));
    }

    public function test_login_includes_organization_memberships(): void
    {
        $organization = Organization::withoutEvents(
            fn () => Organization::factory()->create(['name' => 'UTeM']),
        );

        $orgAdminRoleId = Role::query()->where('slug', Role::ORG_ADMIN)->value('id');
        $user = User::withoutEvents(fn () => User::factory()->create([
            'email' => 'orgadmin@example.com',
        ]));
        $user->organizations()->attach($organization->id, [
            'role_id' => $orgAdminRoleId,
            'status' => 'active',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'orgadmin@example.com',
            'password' => 'password',
            'device_name' => 'phpunit',
        ])
            ->assertOk()
            ->assertJsonPath('data.organizations.0.slug', $organization->slug)
            ->assertJsonPath('data.organizations.0.role', Role::ORG_ADMIN);
    }

    public function test_protected_routes_require_authentication(): void
    {
        $this->getJson('/api/v1/events')->assertUnauthorized();
    }
}