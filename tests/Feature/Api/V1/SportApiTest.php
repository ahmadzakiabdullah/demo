<?php

namespace Tests\Feature\Api\V1;

use App\Enums\SportStatus;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Sport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SportApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_owner_can_create_and_list_sports_via_api(): void
    {
        $admin = User::factory()->admin()->create();
        $organization = Organization::withoutEvents(
            fn () => Organization::factory()->create(),
        );
        $event = Event::withoutEvents(fn () => Event::factory()->create([
            'organization_id' => $organization->id,
        ]));

        Sanctum::actingAs($admin);

        $this->postJson("/api/v1/events/{$event->id}/sports", [
            'name' => 'Esports',
            'template_slug' => 'esports',
            'status' => SportStatus::Active->value,
        ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Esports')
            ->assertJsonPath('data.template_slug', 'esports');

        $this->getJson("/api/v1/events/{$event->id}/sports")
            ->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    public function test_member_cannot_list_sports_via_api(): void
    {
        $event = Event::withoutEvents(fn () => Event::factory()->create());
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->getJson("/api/v1/events/{$event->id}/sports")->assertForbidden();
    }

    public function test_system_owner_can_view_sport_with_structure(): void
    {
        $admin = User::factory()->admin()->create();
        $organization = Organization::withoutEvents(
            fn () => Organization::factory()->create(),
        );
        $event = Event::withoutEvents(fn () => Event::factory()->create([
            'organization_id' => $organization->id,
        ]));

        Sanctum::actingAs($admin);

        $createResponse = $this->postJson("/api/v1/events/{$event->id}/sports", [
            'name' => 'Swimming',
            'template_slug' => 'swimming',
            'status' => SportStatus::Active->value,
        ]);

        $sportId = $createResponse->json('data.id');

        $this->getJson("/api/v1/events/{$event->id}/sports/{$sportId}")
            ->assertOk()
            ->assertJsonPath('data.disciplines.0.slug', 'freestyle');
    }
}