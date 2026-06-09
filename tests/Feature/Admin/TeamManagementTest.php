<?php

namespace Tests\Feature\Admin;

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
use Tests\TestCase;

class TeamManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_access_teams(): void
    {
        $event = Event::withoutEvents(fn () => Event::factory()->create());

        $this->get(route('admin.events.teams.index', $event))
            ->assertRedirect(route('login'));
    }

    public function test_org_admin_can_register_team_for_event(): void
    {
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
        $coach = User::withoutEvents(fn () => User::factory()->create());
        $coach->organizations()->attach($organization->id, [
            'role_id' => Role::query()->where('slug', Role::ORG_ADMIN)->value('id'),
            'status' => 'active',
        ]);

        $orgAdminRoleId = Role::query()->where('slug', Role::ORG_ADMIN)->value('id');
        $orgAdmin = User::withoutEvents(fn () => User::factory()->create());
        $orgAdmin->organizations()->attach($organization->id, [
            'role_id' => $orgAdminRoleId,
            'status' => 'active',
        ]);

        $participant = EventParticipant::withoutEvents(fn () => EventParticipant::factory()->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
        ]));

        $this->actingAs($orgAdmin)
            ->post(route('admin.events.teams.store', $event), [
                'event_participant_id' => $participant->id,
                'sport_id' => $sport->id,
                'name' => 'UTeM Eagles',
                'coach_user_id' => $coach->id,
            ])
            ->assertRedirect();

        $team = Team::query()->where('name', 'UTeM Eagles')->first();

        $this->assertNotNull($team);
        $this->assertSame($coach->id, $team->coach_user_id);
        $this->assertDatabaseHas('registrations', [
            'event_id' => $event->id,
            'sport_id' => $sport->id,
            'registrable_type' => Team::class,
            'registrable_id' => $team->id,
            'status' => RegistrationStatus::Draft->value,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'created',
            'auditable_type' => Team::class,
            'auditable_id' => $team->id,
        ]);
    }

    public function test_org_admin_can_manage_roster_and_advance_registration(): void
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
        $registration = Registration::withoutEvents(fn () => Registration::factory()->create([
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

        $this->actingAs($orgAdmin)
            ->post(route('admin.events.teams.athletes.store', [$event, $team]), [
                'athlete_id' => $athlete->id,
                'role' => TeamMemberRole::Captain->value,
                'jersey_number' => '10',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('team_athlete', [
            'team_id' => $team->id,
            'athlete_id' => $athlete->id,
            'role' => TeamMemberRole::Captain->value,
            'jersey_number' => '10',
        ]);

        $this->actingAs($orgAdmin)
            ->patch(route('admin.events.registrations.status', [$event, $registration]), [
                'status' => RegistrationStatus::Submitted->value,
            ])
            ->assertRedirect();

        $this->actingAs($orgAdmin)
            ->patch(route('admin.events.registrations.status', [$event, $registration->fresh()]), [
                'status' => RegistrationStatus::Approved->value,
            ])
            ->assertRedirect();

        $registration->refresh();
        $this->assertSame(RegistrationStatus::Approved, $registration->status);
    }

    public function test_team_registration_submit_blocked_without_roster(): void
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
        $registration = Registration::withoutEvents(fn () => Registration::factory()->create([
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

        $this->actingAs($orgAdmin)
            ->from(route('admin.events.teams.show', [$event, $team]))
            ->patch(route('admin.events.registrations.status', [$event, $registration]), [
                'status' => RegistrationStatus::Submitted->value,
            ])
            ->assertRedirect(route('admin.events.teams.show', [$event, $team]))
            ->assertSessionHasErrors('roster');
    }

    public function test_member_cannot_register_team(): void
    {
        $event = Event::withoutEvents(fn () => Event::factory()->create());
        $sport = Sport::withoutEvents(fn () => Sport::factory()->create([
            'event_id' => $event->id,
        ]));
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.events.teams.store', $event), [
                'sport_id' => $sport->id,
                'name' => 'Blocked Team',
            ])
            ->assertForbidden();
    }
}