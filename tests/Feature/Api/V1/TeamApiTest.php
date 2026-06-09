<?php

namespace Tests\Feature\Api\V1;

use App\Enums\RegistrationStatus;
use App\Enums\SportStatus;
use App\Enums\TeamMemberRole;
use App\Models\Athlete;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Organization;
use App\Models\Registration;
use App\Models\Role;
use App\Models\Sport;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TeamApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_owner_can_create_and_list_teams_via_api(): void
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
            'status' => SportStatus::Active,
        ]));

        $participant = EventParticipant::withoutEvents(fn () => EventParticipant::factory()->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
        ]));

        Sanctum::actingAs($admin);

        $this->postJson("/api/v1/events/{$event->id}/teams", [
            'event_participant_id' => $participant->id,
            'sport_id' => $sport->id,
            'name' => 'API Warriors',
        ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'API Warriors');

        $this->getJson("/api/v1/events/{$event->id}/teams")
            ->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    public function test_member_cannot_list_teams_via_api(): void
    {
        $event = Event::withoutEvents(fn () => Event::factory()->create());
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->getJson("/api/v1/events/{$event->id}/teams")->assertForbidden();
    }

    public function test_org_admin_can_manage_roster_via_api(): void
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
        $team = Team::withoutEvents(fn () => Team::factory()->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'sport_id' => $sport->id,
        ]));
        $athlete = Athlete::withoutEvents(fn () => Athlete::factory()->create([
            'organization_id' => $organization->id,
        ]));
        Registration::withoutEvents(fn () => Registration::factory()->create([
            'event_id' => $event->id,
            'sport_id' => $sport->id,
            'registrable_type' => Team::class,
            'registrable_id' => $team->id,
            'status' => RegistrationStatus::Draft,
        ]));

        $orgAdminRoleId = Role::query()->where('slug', Role::ORG_ADMIN)->value('id');
        $orgAdmin = User::withoutEvents(fn () => User::factory()->create());
        $orgAdmin->organizations()->attach($organization->id, [
            'role_id' => $orgAdminRoleId,
            'status' => 'active',
        ]);

        Sanctum::actingAs($orgAdmin);

        $this->postJson("/api/v1/events/{$event->id}/teams/{$team->id}/athletes", [
            'athlete_id' => $athlete->id,
            'role' => TeamMemberRole::Member->value,
        ])
            ->assertOk()
            ->assertJsonPath('data.athletes.0.id', $athlete->id);
    }
}