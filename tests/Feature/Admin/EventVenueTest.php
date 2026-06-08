<?php

namespace Tests\Feature\Admin;

use App\Models\Event;
use App\Models\Organization;
use App\Models\Role;
use App\Models\Sport;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EventVenueTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_access_event_venues(): void
    {
        $event = Event::withoutEvents(fn () => Event::factory()->create());

        $this->get(route('admin.events.venues.index', $event))
            ->assertRedirect(route('login'));
    }

    public function test_org_admin_can_attach_venue_to_event(): void
    {
        $organization = Organization::withoutEvents(
            fn () => Organization::factory()->create(),
        );
        $event = Event::withoutEvents(fn () => Event::factory()->create([
            'organization_id' => $organization->id,
        ]));
        $venue = Venue::withoutEvents(fn () => Venue::factory()->create([
            'organization_id' => $organization->id,
        ]));

        $orgAdminRoleId = Role::query()->where('slug', Role::ORG_ADMIN)->value('id');
        $orgAdmin = User::withoutEvents(fn () => User::factory()->create());
        $orgAdmin->organizations()->attach($organization->id, [
            'role_id' => $orgAdminRoleId,
            'status' => 'active',
        ]);

        $this->actingAs($orgAdmin)
            ->post(route('admin.events.venues.store', $event), [
                'venue_id' => $venue->id,
                'is_primary' => true,
                'notes' => 'Main competition venue',
            ])
            ->assertRedirect(route('admin.events.venues.show', [$event, $venue]));

        $this->assertDatabaseHas('event_venue', [
            'event_id' => $event->id,
            'venue_id' => $venue->id,
            'is_primary' => true,
            'notes' => 'Main competition venue',
        ]);
    }

    public function test_org_admin_can_link_and_unlink_sport_to_venue(): void
    {
        $organization = Organization::withoutEvents(
            fn () => Organization::factory()->create(),
        );
        $event = Event::withoutEvents(fn () => Event::factory()->create([
            'organization_id' => $organization->id,
        ]));
        $sport = Sport::withoutEvents(fn () => Sport::factory()->create([
            'event_id' => $event->id,
        ]));
        $venue = Venue::withoutEvents(fn () => Venue::factory()->create([
            'organization_id' => $organization->id,
        ]));
        $event->venues()->attach($venue->id);

        $orgAdminRoleId = Role::query()->where('slug', Role::ORG_ADMIN)->value('id');
        $orgAdmin = User::withoutEvents(fn () => User::factory()->create());
        $orgAdmin->organizations()->attach($organization->id, [
            'role_id' => $orgAdminRoleId,
            'status' => 'active',
        ]);

        $this->actingAs($orgAdmin)
            ->post(route('admin.events.venues.sports.store', [$event, $venue]), [
                'sport_id' => $sport->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('event_sport_venue', [
            'event_id' => $event->id,
            'sport_id' => $sport->id,
            'venue_id' => $venue->id,
        ]);

        $this->actingAs($orgAdmin)
            ->delete(route('admin.events.venues.sports.destroy', [$event, $venue, $sport]))
            ->assertRedirect();

        $this->assertDatabaseMissing('event_sport_venue', [
            'event_id' => $event->id,
            'sport_id' => $sport->id,
            'venue_id' => $venue->id,
        ]);
    }

    public function test_detach_venue_removes_sport_links(): void
    {
        $organization = Organization::withoutEvents(
            fn () => Organization::factory()->create(),
        );
        $event = Event::withoutEvents(fn () => Event::factory()->create([
            'organization_id' => $organization->id,
        ]));
        $sport = Sport::withoutEvents(fn () => Sport::factory()->create([
            'event_id' => $event->id,
        ]));
        $venue = Venue::withoutEvents(fn () => Venue::factory()->create([
            'organization_id' => $organization->id,
        ]));
        $event->venues()->attach($venue->id);
        DB::table('event_sport_venue')->insert([
            'event_id' => $event->id,
            'sport_id' => $sport->id,
            'venue_id' => $venue->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $orgAdminRoleId = Role::query()->where('slug', Role::ORG_ADMIN)->value('id');
        $orgAdmin = User::withoutEvents(fn () => User::factory()->create());
        $orgAdmin->organizations()->attach($organization->id, [
            'role_id' => $orgAdminRoleId,
            'status' => 'active',
        ]);

        $this->actingAs($orgAdmin)
            ->delete(route('admin.events.venues.destroy', [$event, $venue]))
            ->assertRedirect(route('admin.events.venues.index', $event));

        $this->assertDatabaseMissing('event_venue', [
            'event_id' => $event->id,
            'venue_id' => $venue->id,
        ]);
        $this->assertDatabaseMissing('event_sport_venue', [
            'event_id' => $event->id,
            'sport_id' => $sport->id,
            'venue_id' => $venue->id,
        ]);
    }

    public function test_member_cannot_attach_venue(): void
    {
        $organization = Organization::withoutEvents(
            fn () => Organization::factory()->create(),
        );
        $event = Event::withoutEvents(fn () => Event::factory()->create([
            'organization_id' => $organization->id,
        ]));
        $venue = Venue::withoutEvents(fn () => Venue::factory()->create([
            'organization_id' => $organization->id,
        ]));
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.events.venues.store', $event), [
                'venue_id' => $venue->id,
            ])
            ->assertForbidden();
    }
}