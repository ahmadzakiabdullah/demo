<?php

namespace Tests\Feature\Api\V1;

use App\Enums\RegistrationStatus;
use App\Enums\SportGender;
use App\Enums\SportStatus;
use App\Models\Athlete;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Registration;
use App\Models\Role;
use App\Models\Sport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AthleteApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_owner_can_create_and_list_athletes_via_api(): void
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

        Sanctum::actingAs($admin);

        $this->postJson("/api/v1/events/{$event->id}/athletes", [
            'sport_id' => $sport->id,
            'name' => 'Siti Aminah',
            'dob' => '2004-06-01',
            'gender' => SportGender::Female->value,
            'nationality' => 'MY',
            'id_number' => 'B9876543',
            'medical_clearance' => true,
        ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Siti Aminah');

        $this->getJson("/api/v1/events/{$event->id}/athletes")
            ->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    public function test_member_cannot_list_athletes_via_api(): void
    {
        $event = Event::withoutEvents(fn () => Event::factory()->create());
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->getJson("/api/v1/events/{$event->id}/athletes")->assertForbidden();
    }

    public function test_org_admin_can_update_registration_status_via_api(): void
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

        Sanctum::actingAs($orgAdmin);

        $this->patchJson("/api/v1/events/{$event->id}/registrations/{$registration->id}/status", [
            'status' => RegistrationStatus::Submitted->value,
        ])
            ->assertOk()
            ->assertJsonPath('data.status', RegistrationStatus::Submitted->value);
    }
}