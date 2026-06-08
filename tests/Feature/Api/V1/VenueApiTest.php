<?php

namespace Tests\Feature\Api\V1;

use App\Enums\FacilityType;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Sport;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VenueApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_owner_can_list_venues_without_organization_header(): void
    {
        $admin = User::factory()->admin()->create();
        $organization = Organization::withoutEvents(
            fn () => Organization::factory()->create(),
        );

        Venue::withoutEvents(fn () => Venue::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Fallback Arena',
        ]));

        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/venues')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.name', 'Fallback Arena');
    }

    public function test_system_owner_can_create_and_list_venues_via_api(): void
    {
        $admin = User::factory()->admin()->create();
        $organization = Organization::withoutEvents(
            fn () => Organization::factory()->create(),
        );

        Sanctum::actingAs($admin);

        $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->postJson('/api/v1/venues', [
                'name' => 'Olympic Stadium',
                'address' => 'Bukit Jalil',
                'capacity' => 87411,
                'timezone' => 'Asia/Kuala_Lumpur',
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Olympic Stadium')
            ->assertJsonPath('data.slug', 'olympic-stadium');

        $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->getJson('/api/v1/venues')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    public function test_member_cannot_list_venues_via_api(): void
    {
        $organization = Organization::withoutEvents(
            fn () => Organization::factory()->create(),
        );
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->getJson('/api/v1/venues')
            ->assertForbidden();
    }

    public function test_system_owner_can_manage_event_venues_via_api(): void
    {
        $admin = User::factory()->admin()->create();
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

        Sanctum::actingAs($admin);

        $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->postJson("/api/v1/events/{$event->id}/venues", [
                'venue_id' => $venue->id,
                'is_primary' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.id', $venue->id);

        $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->getJson("/api/v1/events/{$event->id}/venues")
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->postJson("/api/v1/events/{$event->id}/venues/{$venue->id}/sports", [
                'sport_id' => $sport->id,
            ])
            ->assertCreated()
            ->assertJsonPath('data.sport.id', $sport->id);

        $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->getJson("/api/v1/events/{$event->id}/venues/{$venue->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $venue->id);

        $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->postJson("/api/v1/venues/{$venue->id}/facilities", [
                'name' => 'Track 1',
                'type' => FacilityType::Track->value,
                'capacity' => 8,
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Track 1');
    }
}