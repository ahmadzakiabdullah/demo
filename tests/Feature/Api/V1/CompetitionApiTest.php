<?php

namespace Tests\Feature\Api\V1;

use App\Models\Competition;
use App\Models\CompetitionFormat;
use App\Models\Event;
use App\Models\Fixture;
use App\Models\Organization;
use App\Models\Role;
use App\Models\Sport;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CompetitionApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_owner_can_create_and_list_competitions_via_api(): void
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
        $format = CompetitionFormat::query()->where('slug', 'league')->firstOrFail();

        Sanctum::actingAs($admin);

        $this->postJson("/api/v1/events/{$event->id}/competitions", [
            'sport_id' => $sport->id,
            'competition_format_id' => $format->id,
            'name' => 'API League',
        ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'API League');

        $this->getJson("/api/v1/events/{$event->id}/competitions")
            ->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    public function test_member_cannot_list_competitions_via_api(): void
    {
        $event = Event::withoutEvents(fn () => Event::factory()->create());
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->getJson("/api/v1/events/{$event->id}/competitions")->assertForbidden();
    }

    public function test_org_admin_can_create_fixture_and_match_via_api(): void
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
        $format = CompetitionFormat::query()->firstOrFail();
        $competition = Competition::withoutEvents(fn () => Competition::factory()->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'sport_id' => $sport->id,
            'competition_format_id' => $format->id,
        ]));
        $teams = Team::withoutEvents(fn () => Team::factory()->count(2)->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'sport_id' => $sport->id,
        ]));

        $orgAdminRoleId = Role::query()->where('slug', Role::ORG_ADMIN)->value('id');
        $orgAdmin = User::withoutEvents(fn () => User::factory()->create());
        $orgAdmin->organizations()->attach($organization->id, [
            'role_id' => $orgAdminRoleId,
            'status' => 'active',
        ]);

        Sanctum::actingAs($orgAdmin);

        $this->postJson("/api/v1/events/{$event->id}/competitions/{$competition->id}/fixtures", [
            'name' => 'Finals Day',
            'round' => 'Final',
        ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Finals Day');

        $fixture = Fixture::query()->firstOrFail();

        $this->postJson("/api/v1/events/{$event->id}/competitions/{$competition->id}/fixtures/{$fixture->id}/matches", [
            'scheduled_at' => now()->addDay()->toIso8601String(),
            'participants' => [
                [
                    'side' => 'home',
                    'participant_type' => Team::class,
                    'participant_id' => $teams[0]->id,
                ],
                [
                    'side' => 'away',
                    'participant_type' => Team::class,
                    'participant_id' => $teams[1]->id,
                ],
            ],
        ])
            ->assertCreated()
            ->assertJsonCount(2, 'data.participants');
    }

    public function test_competition_formats_endpoint_returns_seeded_formats(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/competition-formats')
            ->assertOk()
            ->assertJsonCount(7, 'data');
    }
}