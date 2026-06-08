<?php

namespace Tests\Feature\Admin;

use App\Enums\EventStatus;
use App\Enums\SportStatus;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\EventType;
use App\Models\Organization;
use App\Models\Role;
use App\Models\Sport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SportManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_access_sports(): void
    {
        $event = Event::withoutEvents(fn () => Event::factory()->create());

        $this->get(route('admin.events.sports.index', $event))
            ->assertRedirect(route('login'));
    }

    public function test_system_owner_can_create_sport_from_template(): void
    {
        $admin = User::factory()->admin()->create();
        $organization = Organization::withoutEvents(
            fn () => Organization::factory()->create(),
        );
        $event = Event::withoutEvents(fn () => Event::factory()->create([
            'organization_id' => $organization->id,
            'status' => EventStatus::Draft,
        ]));

        $this->actingAs($admin)
            ->post(route('admin.events.sports.store', $event), [
                'name' => 'Football',
                'template_slug' => 'football',
                'status' => SportStatus::Active->value,
            ])
            ->assertRedirect();

        $sport = Sport::query()->where('event_id', $event->id)->first();

        $this->assertNotNull($sport);
        $this->assertSame('football', $sport->template_slug);
        $this->assertDatabaseHas('sport_disciplines', [
            'sport_id' => $sport->id,
            'slug' => '11-a-side',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'created',
            'auditable_type' => Sport::class,
            'auditable_id' => $sport->id,
        ]);
    }

    public function test_org_admin_can_manage_sports_for_their_event(): void
    {
        $organization = Organization::withoutEvents(
            fn () => Organization::factory()->create(),
        );
        $event = Event::withoutEvents(fn () => Event::factory()->create([
            'organization_id' => $organization->id,
        ]));

        $orgAdminRoleId = Role::query()->where('slug', Role::ORG_ADMIN)->value('id');
        $orgAdmin = User::withoutEvents(fn () => User::factory()->create());
        $orgAdmin->organizations()->attach($organization->id, [
            'role_id' => $orgAdminRoleId,
            'status' => 'active',
        ]);

        $this->actingAs($orgAdmin)
            ->post(route('admin.events.sports.store', $event), [
                'name' => 'Badminton',
                'template_slug' => 'badminton',
                'status' => SportStatus::Active->value,
            ])
            ->assertRedirect();

        $sport = Sport::query()->where('slug', 'badminton')->first();

        $this->actingAs($orgAdmin)
            ->get(route('admin.events.sports.show', [$event, $sport]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Events/Sports/Show')
                ->where('sport.name', 'Badminton')
                ->has('sport.disciplines', 2));
    }

    public function test_member_cannot_create_sport(): void
    {
        $event = Event::withoutEvents(fn () => Event::factory()->create());
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.events.sports.store', $event), [
                'name' => 'Swimming',
                'status' => SportStatus::Active->value,
            ])
            ->assertForbidden();
    }

    public function test_sport_slug_is_unique_within_event(): void
    {
        $admin = User::factory()->admin()->create();
        $organization = Organization::withoutEvents(
            fn () => Organization::factory()->create(),
        );
        $event = Event::withoutEvents(fn () => Event::factory()->create([
            'organization_id' => $organization->id,
        ]));

        Sport::withoutEvents(fn () => Sport::factory()->create([
            'event_id' => $event->id,
            'name' => 'Athletics',
            'slug' => 'athletics',
        ]));

        $this->actingAs($admin)
            ->post(route('admin.events.sports.store', $event), [
                'name' => 'Athletics',
                'status' => SportStatus::Active->value,
            ])
            ->assertRedirect();

        $this->assertSame(2, Sport::query()->where('event_id', $event->id)->count());
        $this->assertDatabaseHas('sports', [
            'event_id' => $event->id,
            'slug' => 'athletics-1',
        ]);
    }
}