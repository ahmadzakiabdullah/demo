<?php

namespace Tests\Feature\Api\V1;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\EventType;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EventApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_owner_can_create_and_list_events(): void
    {
        $admin = User::factory()->admin()->create();
        $organization = Organization::withoutEvents(
            fn () => Organization::factory()->create(),
        );

        Sanctum::actingAs($admin);

        $this->postJson('/api/v1/events', [
            'organization_id' => $organization->id,
            'event_type_id' => EventType::query()->first()->id,
            'event_category_id' => EventCategory::query()->first()->id,
            'name' => 'API Sports Day',
            'status' => EventStatus::Draft->value,
            'location' => 'Stadium',
        ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'API Sports Day')
            ->assertJsonPath('data.slug', 'api-sports-day');

        $this->getJson('/api/v1/events')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    public function test_org_admin_can_create_event_for_their_organization(): void
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

        Sanctum::actingAs($orgAdmin);

        $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->postJson('/api/v1/events', [
                'organization_id' => $organization->id,
                'event_type_id' => EventType::query()->first()->id,
                'event_category_id' => EventCategory::query()->first()->id,
                'name' => 'Faculty Games API',
                'status' => EventStatus::Draft->value,
            ])
            ->assertCreated();

        $this->assertDatabaseHas('events', [
            'organization_id' => $organization->id,
            'name' => 'Faculty Games API',
        ]);
    }

    public function test_org_admin_cannot_create_event_for_other_organization(): void
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

        Sanctum::actingAs($orgAdmin);

        $this->postJson('/api/v1/events', [
            'organization_id' => $otherOrganization->id,
            'event_type_id' => EventType::query()->first()->id,
            'event_category_id' => EventCategory::query()->first()->id,
            'name' => 'Forbidden Event',
            'status' => EventStatus::Draft->value,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['organization_id']);
    }

    public function test_event_status_can_be_updated_via_api(): void
    {
        $admin = User::factory()->admin()->create();
        $organization = Organization::withoutEvents(
            fn () => Organization::factory()->create(),
        );

        $event = Event::withoutEvents(fn () => Event::factory()->create([
            'organization_id' => $organization->id,
            'status' => EventStatus::Draft,
        ]));

        Sanctum::actingAs($admin);

        $this->patchJson("/api/v1/events/{$event->id}/status", [
            'status' => EventStatus::Published->value,
        ])
            ->assertOk()
            ->assertJsonPath('data.status', EventStatus::Published->value);

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'status' => EventStatus::Published->value,
        ]);
    }

    public function test_member_cannot_list_events(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/events')->assertForbidden();
    }
}