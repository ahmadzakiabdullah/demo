<?php

namespace Tests\Feature\Admin;

use App\Enums\RegistrationStatus;
use App\Enums\SportGender;
use App\Enums\SportStatus;
use App\Models\Athlete;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Registration;
use App\Models\Role;
use App\Models\Sport;
use App\Models\SportCategory;
use App\Models\SportDiscipline;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AthleteManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_access_athletes(): void
    {
        $event = Event::withoutEvents(fn () => Event::factory()->create());

        $this->get(route('admin.events.athletes.index', $event))
            ->assertRedirect(route('login'));
    }

    public function test_org_admin_can_register_athlete_for_event(): void
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

        $orgAdminRoleId = Role::query()->where('slug', Role::ORG_ADMIN)->value('id');
        $orgAdmin = User::withoutEvents(fn () => User::factory()->create());
        $orgAdmin->organizations()->attach($organization->id, [
            'role_id' => $orgAdminRoleId,
            'status' => 'active',
        ]);

        $this->actingAs($orgAdmin)
            ->post(route('admin.events.athletes.store', $event), [
                'sport_id' => $sport->id,
                'name' => 'Ahmad Ali',
                'dob' => '2005-01-15',
                'gender' => SportGender::Male->value,
                'nationality' => 'MY',
                'id_number' => 'A1234567',
                'medical_clearance' => true,
            ])
            ->assertRedirect();

        $athlete = Athlete::query()->where('name', 'Ahmad Ali')->first();

        $this->assertNotNull($athlete);
        $this->assertSame($organization->id, $athlete->organization_id);
        $this->assertDatabaseHas('registrations', [
            'event_id' => $event->id,
            'sport_id' => $sport->id,
            'registrable_type' => Athlete::class,
            'registrable_id' => $athlete->id,
            'status' => RegistrationStatus::Draft->value,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'created',
            'auditable_type' => Athlete::class,
            'auditable_id' => $athlete->id,
        ]);
    }

    public function test_org_admin_can_advance_registration_workflow(): void
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
        $athlete = Athlete::withoutEvents(fn () => Athlete::factory()->create([
            'organization_id' => $organization->id,
            'medical_clearance' => true,
        ]));
        $registration = Registration::withoutEvents(fn () => Registration::factory()->create([
            'event_id' => $event->id,
            'sport_id' => $sport->id,
            'registrable_type' => Athlete::class,
            'registrable_id' => $athlete->id,
            'status' => RegistrationStatus::Draft,
        ]));

        $orgAdminRoleId = Role::query()->where('slug', Role::ORG_ADMIN)->value('id');
        $orgAdmin = User::withoutEvents(fn () => User::factory()->create());
        $orgAdmin->organizations()->attach($organization->id, [
            'role_id' => $orgAdminRoleId,
            'status' => 'active',
        ]);

        $this->actingAs($orgAdmin)
            ->patch(route('admin.events.registrations.status', [$event, $registration]), [
                'status' => RegistrationStatus::Submitted->value,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('registrations', [
            'id' => $registration->id,
            'status' => RegistrationStatus::Submitted->value,
        ]);

        $this->actingAs($orgAdmin)
            ->patch(route('admin.events.registrations.status', [$event, $registration->fresh()]), [
                'status' => RegistrationStatus::Verified->value,
            ])
            ->assertRedirect();

        $this->actingAs($orgAdmin)
            ->patch(route('admin.events.registrations.status', [$event, $registration->fresh()]), [
                'status' => RegistrationStatus::Approved->value,
            ])
            ->assertRedirect();

        $registration->refresh();
        $this->assertSame(RegistrationStatus::Approved, $registration->status);
        $this->assertNotNull($registration->approved_at);
    }

    public function test_registration_submit_blocked_without_medical_clearance(): void
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
        $athlete = Athlete::withoutEvents(fn () => Athlete::factory()->create([
            'organization_id' => $organization->id,
            'medical_clearance' => false,
        ]));
        $registration = Registration::withoutEvents(fn () => Registration::factory()->create([
            'event_id' => $event->id,
            'sport_id' => $sport->id,
            'registrable_type' => Athlete::class,
            'registrable_id' => $athlete->id,
            'status' => RegistrationStatus::Draft,
        ]));

        $orgAdminRoleId = Role::query()->where('slug', Role::ORG_ADMIN)->value('id');
        $orgAdmin = User::withoutEvents(fn () => User::factory()->create());
        $orgAdmin->organizations()->attach($organization->id, [
            'role_id' => $orgAdminRoleId,
            'status' => 'active',
        ]);

        $this->actingAs($orgAdmin)
            ->from(route('admin.events.athletes.show', [$event, $athlete]))
            ->patch(route('admin.events.registrations.status', [$event, $registration]), [
                'status' => RegistrationStatus::Submitted->value,
            ])
            ->assertRedirect(route('admin.events.athletes.show', [$event, $athlete]))
            ->assertSessionHasErrors('eligibility');
    }

    public function test_eligibility_checks_category_age_rules(): void
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
        $discipline = SportDiscipline::query()->create([
            'sport_id' => $sport->id,
            'name' => 'Track',
            'slug' => 'track',
        ]);
        $category = SportCategory::query()->create([
            'sport_discipline_id' => $discipline->id,
            'name' => 'U18',
            'slug' => 'u18',
            'gender' => SportGender::Male,
            'min_age' => 16,
            'max_age' => 18,
        ]);
        $athlete = Athlete::withoutEvents(fn () => Athlete::factory()->create([
            'organization_id' => $organization->id,
            'dob' => now()->subYears(25),
            'gender' => SportGender::Male,
            'medical_clearance' => true,
        ]));
        $registration = Registration::withoutEvents(fn () => Registration::factory()->create([
            'event_id' => $event->id,
            'sport_id' => $sport->id,
            'sport_category_id' => $category->id,
            'registrable_type' => Athlete::class,
            'registrable_id' => $athlete->id,
            'status' => RegistrationStatus::Draft,
        ]));

        $orgAdminRoleId = Role::query()->where('slug', Role::ORG_ADMIN)->value('id');
        $orgAdmin = User::withoutEvents(fn () => User::factory()->create());
        $orgAdmin->organizations()->attach($organization->id, [
            'role_id' => $orgAdminRoleId,
            'status' => 'active',
        ]);

        $this->actingAs($orgAdmin)
            ->patch(route('admin.events.registrations.status', [$event, $registration]), [
                'status' => RegistrationStatus::Submitted->value,
            ])
            ->assertSessionHasErrors('eligibility');
    }

    public function test_member_cannot_register_athlete(): void
    {
        $event = Event::withoutEvents(fn () => Event::factory()->create());
        $sport = Sport::withoutEvents(fn () => Sport::factory()->create([
            'event_id' => $event->id,
        ]));
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.events.athletes.store', $event), [
                'sport_id' => $sport->id,
                'name' => 'Blocked Athlete',
                'medical_clearance' => true,
            ])
            ->assertForbidden();
    }
}