<?php

namespace Tests\Feature\Admin;

use App\Enums\OfficialType;
use App\Enums\RegistrationStatus;
use App\Enums\SportStatus;
use App\Models\Event;
use App\Models\Official;
use App\Models\Organization;
use App\Models\Registration;
use App\Models\Role;
use App\Models\Sport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfficialManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_access_officials(): void
    {
        $event = Event::withoutEvents(fn () => Event::factory()->create());

        $this->get(route('admin.events.officials.index', $event))
            ->assertRedirect(route('login'));
    }

    public function test_org_admin_can_register_official_for_event(): void
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
            ->post(route('admin.events.officials.store', $event), [
                'sport_id' => $sport->id,
                'name' => 'Ravi Kumar',
                'email' => 'ravi@example.com',
                'type' => OfficialType::Referee->value,
                'certification_level' => 'National',
                'certification_expires_at' => now()->addYear()->toDateString(),
            ])
            ->assertRedirect();

        $official = Official::query()->where('name', 'Ravi Kumar')->first();

        $this->assertNotNull($official);
        $this->assertSame($organization->id, $official->organization_id);
        $this->assertDatabaseHas('registrations', [
            'event_id' => $event->id,
            'sport_id' => $sport->id,
            'registrable_type' => Official::class,
            'registrable_id' => $official->id,
            'status' => RegistrationStatus::Draft->value,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'created',
            'auditable_type' => Official::class,
            'auditable_id' => $official->id,
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
        $official = Official::withoutEvents(fn () => Official::factory()->create([
            'organization_id' => $organization->id,
            'certification_expires_at' => now()->addYear(),
        ]));
        $registration = Registration::withoutEvents(fn () => Registration::factory()->create([
            'event_id' => $event->id,
            'sport_id' => $sport->id,
            'registrable_type' => Official::class,
            'registrable_id' => $official->id,
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

    public function test_registration_submit_blocked_with_expired_certification(): void
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
        $official = Official::withoutEvents(fn () => Official::factory()->expiredCertification()->create([
            'organization_id' => $organization->id,
        ]));
        $registration = Registration::withoutEvents(fn () => Registration::factory()->create([
            'event_id' => $event->id,
            'sport_id' => $sport->id,
            'registrable_type' => Official::class,
            'registrable_id' => $official->id,
            'status' => RegistrationStatus::Draft,
        ]));

        $orgAdminRoleId = Role::query()->where('slug', Role::ORG_ADMIN)->value('id');
        $orgAdmin = User::withoutEvents(fn () => User::factory()->create());
        $orgAdmin->organizations()->attach($organization->id, [
            'role_id' => $orgAdminRoleId,
            'status' => 'active',
        ]);

        $this->actingAs($orgAdmin)
            ->from(route('admin.events.officials.show', [$event, $official]))
            ->patch(route('admin.events.registrations.status', [$event, $registration]), [
                'status' => RegistrationStatus::Submitted->value,
            ])
            ->assertRedirect(route('admin.events.officials.show', [$event, $official]))
            ->assertSessionHasErrors('eligibility');
    }

    public function test_member_cannot_register_official(): void
    {
        $event = Event::withoutEvents(fn () => Event::factory()->create());
        $sport = Sport::withoutEvents(fn () => Sport::factory()->create([
            'event_id' => $event->id,
        ]));
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.events.officials.store', $event), [
                'sport_id' => $sport->id,
                'name' => 'Blocked Official',
                'type' => OfficialType::Judge->value,
                'certification_expires_at' => now()->addYear()->toDateString(),
            ])
            ->assertForbidden();
    }
}