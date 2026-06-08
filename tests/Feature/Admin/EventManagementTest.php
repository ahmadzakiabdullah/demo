<?php

namespace Tests\Feature\Admin;

use App\Enums\EventAssignmentRole;
use App\Enums\EventStatus;
use App\Models\AuditLog;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\EventType;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_access_events(): void
    {
        $this->get(route('admin.events.index'))->assertRedirect(route('login'));
    }

    public function test_member_cannot_access_events(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.events.index'))
            ->assertForbidden();
    }

    public function test_system_owner_can_create_and_view_event(): void
    {
        $admin = User::factory()->admin()->create();
        $organization = Organization::withoutEvents(
            fn () => Organization::factory()->create(),
        );

        $this->actingAs($admin)
            ->post(route('admin.events.store'), [
                'organization_id' => $organization->id,
                'event_type_id' => EventType::query()->first()->id,
                'event_category_id' => EventCategory::query()->first()->id,
                'name' => 'UTeM Sports Carnival 2026',
                'status' => EventStatus::Draft->value,
                'location' => 'Main Campus',
                'description' => 'Annual university sports event.',
                'starts_at' => '2026-08-01T08:00',
                'ends_at' => '2026-08-05T18:00',
            ])
            ->assertRedirect();

        $event = Event::query()->where('slug', 'utem-sports-carnival-2026')->first();

        $this->assertNotNull($event);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'created',
            'auditable_type' => Event::class,
            'auditable_id' => $event->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.events.show', $event))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Events/Show')
                ->where('event.name', 'UTeM Sports Carnival 2026')
                ->where('event.stats.participants_count', 0));
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

        $this->actingAs($orgAdmin)
            ->post(route('admin.events.store'), [
                'organization_id' => $organization->id,
                'event_type_id' => EventType::query()->first()->id,
                'event_category_id' => EventCategory::query()->first()->id,
                'name' => 'Faculty Games',
                'status' => EventStatus::Draft->value,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('events', [
            'organization_id' => $organization->id,
            'name' => 'Faculty Games',
            'status' => EventStatus::Draft->value,
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

        $this->actingAs($orgAdmin)
            ->post(route('admin.events.store'), [
                'organization_id' => $otherOrganization->id,
                'event_type_id' => EventType::query()->first()->id,
                'event_category_id' => EventCategory::query()->first()->id,
                'name' => 'Blocked Event',
                'status' => EventStatus::Draft->value,
            ])
            ->assertSessionHasErrors('organization_id');
    }

    public function test_slug_is_unique_within_organization(): void
    {
        $admin = User::factory()->admin()->create();
        $organization = Organization::withoutEvents(
            fn () => Organization::factory()->create(),
        );

        Event::withoutEvents(fn () => Event::factory()->create([
            'organization_id' => $organization->id,
            'slug' => 'sports-day',
        ]));

        $this->actingAs($admin)
            ->post(route('admin.events.store'), [
                'organization_id' => $organization->id,
                'event_type_id' => EventType::query()->first()->id,
                'event_category_id' => EventCategory::query()->first()->id,
                'name' => 'Sports Day',
                'slug' => 'sports-day',
                'status' => EventStatus::Draft->value,
            ])
            ->assertSessionHasErrors('slug');
    }

    public function test_status_transition_is_validated(): void
    {
        $organization = Organization::withoutEvents(
            fn () => Organization::factory()->create(),
        );
        $event = Event::withoutEvents(fn () => Event::factory()->create([
            'organization_id' => $organization->id,
            'status' => EventStatus::Draft,
        ]));

        $orgAdminRoleId = Role::query()->where('slug', Role::ORG_ADMIN)->value('id');
        $orgAdmin = User::withoutEvents(fn () => User::factory()->create());
        $orgAdmin->organizations()->attach($organization->id, [
            'role_id' => $orgAdminRoleId,
            'status' => 'active',
        ]);

        $this->actingAs($orgAdmin)
            ->put(route('admin.events.update', $event), [
                'event_type_id' => $event->event_type_id,
                'event_category_id' => $event->event_category_id,
                'name' => $event->name,
                'slug' => $event->slug,
                'status' => EventStatus::Active->value,
                'location' => $event->location,
                'description' => $event->description,
                'starts_at' => $event->starts_at?->format('Y-m-d\TH:i'),
                'ends_at' => $event->ends_at?->format('Y-m-d\TH:i'),
            ])
            ->assertSessionHasErrors('status');
    }

    public function test_event_assignments_can_be_managed(): void
    {
        $admin = User::factory()->admin()->create();
        $organization = Organization::withoutEvents(
            fn () => Organization::factory()->create(),
        );
        $event = Event::withoutEvents(fn () => Event::factory()->create([
            'organization_id' => $organization->id,
        ]));

        $member = User::withoutEvents(fn () => User::factory()->create());
        $member->organizations()->attach($organization->id, [
            'role_id' => Role::query()->where('slug', Role::ATHLETE)->value('id'),
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.events.assignments.store', $event), [
                'user_id' => $member->id,
                'role' => EventAssignmentRole::EventOrganizer->value,
            ])
            ->assertRedirect(route('admin.events.show', $event));

        $this->assertDatabaseHas('event_user', [
            'event_id' => $event->id,
            'user_id' => $member->id,
            'role' => EventAssignmentRole::EventOrganizer->value,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.events.assignments.destroy', [$event, $member]))
            ->assertRedirect(route('admin.events.show', $event));

        $this->assertDatabaseMissing('event_user', [
            'event_id' => $event->id,
            'user_id' => $member->id,
        ]);
    }

    public function test_assigned_organizer_can_view_event(): void
    {
        $organization = Organization::withoutEvents(
            fn () => Organization::factory()->create(),
        );
        $event = Event::withoutEvents(fn () => Event::factory()->create([
            'organization_id' => $organization->id,
        ]));

        $organizer = User::withoutEvents(fn () => User::factory()->create());
        $organizer->organizations()->attach($organization->id, [
            'role_id' => Role::query()->where('slug', Role::ATHLETE)->value('id'),
            'status' => 'active',
        ]);
        $event->assignees()->attach($organizer->id, [
            'role' => EventAssignmentRole::EventOrganizer->value,
        ]);

        $this->actingAs($organizer)
            ->get(route('admin.events.show', $event))
            ->assertOk();
    }
}