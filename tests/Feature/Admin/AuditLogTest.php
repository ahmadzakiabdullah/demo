<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_organization_writes_audit_log(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.organizations.store'), [
                'name' => 'Audit Test Org',
                'type' => 'club',
                'timezone' => 'Asia/Kuala_Lumpur',
                'locale' => 'en',
                'status' => 'active',
            ])
            ->assertRedirect(route('admin.organizations.index'));

        $organization = Organization::query()->where('slug', 'audit-test-org')->first();

        $this->assertNotNull($organization);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'created',
            'auditable_type' => Organization::class,
            'auditable_id' => $organization->id,
            'user_id' => $admin->id,
            'organization_id' => $organization->id,
        ]);
    }

    public function test_updating_user_writes_audit_log_without_password(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['name' => 'Before Name']);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $user), [
                'name' => 'After Name',
                'email' => $user->email,
                'system_role' => '',
            ]);

        $log = AuditLog::query()
            ->where('auditable_type', User::class)
            ->where('auditable_id', $user->id)
            ->where('action', 'updated')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('Before Name', $log->old_values['name'] ?? null);
        $this->assertSame('After Name', $log->new_values['name'] ?? null);
        $this->assertArrayNotHasKey('password', $log->old_values ?? []);
        $this->assertArrayNotHasKey('password', $log->new_values ?? []);
    }

    public function test_audit_logs_are_append_only(): void
    {
        $log = AuditLog::query()->create([
            'action' => 'created',
            'auditable_type' => Organization::class,
            'auditable_id' => 1,
            'new_values' => ['name' => 'Test'],
        ]);

        $this->expectException(\RuntimeException::class);
        $log->update(['action' => 'updated']);
    }

    public function test_system_owner_can_view_audit_logs(): void
    {
        $admin = User::factory()->admin()->create();

        AuditLog::query()->create([
            'action' => 'created',
            'auditable_type' => Organization::class,
            'auditable_id' => 1,
            'organization_id' => null,
            'user_id' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.audit-logs.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/AuditLogs/Index')
                ->has('logs.data', 2));
    }

    public function test_org_admin_can_view_organization_audit_logs(): void
    {
        $organization = Organization::withoutEvents(
            fn () => Organization::factory()->create(),
        );

        $orgAdminRoleId = Role::query()
            ->where('slug', Role::ORG_ADMIN)
            ->value('id');

        $orgAdmin = User::withoutEvents(fn () => User::factory()->create());
        $orgAdmin->organizations()->attach($organization->id, [
            'role_id' => $orgAdminRoleId,
            'status' => 'active',
        ]);

        AuditLog::query()->create([
            'action' => 'updated',
            'auditable_type' => Organization::class,
            'auditable_id' => $organization->id,
            'organization_id' => $organization->id,
            'user_id' => $orgAdmin->id,
            'new_values' => ['name' => 'Updated'],
        ]);

        $otherOrganization = Organization::withoutEvents(
            fn () => Organization::factory()->create(),
        );

        AuditLog::query()->create([
            'action' => 'created',
            'auditable_type' => Organization::class,
            'auditable_id' => $otherOrganization->id,
            'organization_id' => $otherOrganization->id,
            'user_id' => $orgAdmin->id,
        ]);

        $this->actingAs($orgAdmin)
            ->get(route('admin.audit-logs.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/AuditLogs/Index')
                ->has('logs.data', 1)
                ->where('logs.data.0.organization.id', $organization->id));
    }

    public function test_member_cannot_view_audit_logs(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.audit-logs.index'))
            ->assertForbidden();
    }

    public function test_register_route_is_rate_limited(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('register'), [
                'name' => "User {$attempt}",
                'email' => "user{$attempt}@example.com",
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);
        }

        $response = $this->post(route('register'), [
            'name' => 'Blocked User',
            'email' => 'blocked@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(429);
    }
}