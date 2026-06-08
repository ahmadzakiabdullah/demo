<?php

namespace Tests\Feature\Api\V1;

use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuditLogApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_owner_can_list_audit_logs(): void
    {
        $admin = User::withoutEvents(fn () => User::factory()->admin()->create());
        $organization = Organization::withoutEvents(
            fn () => Organization::factory()->create(),
        );

        AuditLog::query()->create([
            'user_id' => $admin->id,
            'organization_id' => $organization->id,
            'action' => 'created',
            'auditable_type' => Organization::class,
            'auditable_id' => $organization->id,
            'old_values' => null,
            'new_values' => ['name' => $organization->name],
            'ip_address' => '127.0.0.1',
        ]);

        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/audit-logs')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.action', 'created');
    }

    public function test_org_admin_can_list_audit_logs_for_their_organization(): void
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

        AuditLog::query()->create([
            'user_id' => $orgAdmin->id,
            'organization_id' => $organization->id,
            'action' => 'updated',
            'auditable_type' => Organization::class,
            'auditable_id' => $organization->id,
            'old_values' => ['name' => 'Old'],
            'new_values' => ['name' => 'New'],
            'ip_address' => '127.0.0.1',
        ]);

        AuditLog::query()->create([
            'user_id' => $orgAdmin->id,
            'organization_id' => $otherOrganization->id,
            'action' => 'created',
            'auditable_type' => Organization::class,
            'auditable_id' => $otherOrganization->id,
            'old_values' => null,
            'new_values' => ['name' => 'Other'],
            'ip_address' => '127.0.0.1',
        ]);

        Sanctum::actingAs($orgAdmin);

        $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->getJson('/api/v1/audit-logs')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.organization.id', $organization->id);
    }

    public function test_member_cannot_list_audit_logs(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/audit-logs')->assertForbidden();
    }
}