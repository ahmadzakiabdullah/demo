<?php

namespace Tests\Feature\Api\V1;

use App\Enums\EventParticipantStatus;
use App\Enums\EventParticipantType;
use App\Enums\RegistrationStatus;
use App\Enums\SportStatus;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Organization;
use App\Models\ParticipantSportEntry;
use App\Models\Sport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EventParticipantApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_owner_can_create_and_list_participants_via_api(): void
    {
        $admin = User::factory()->admin()->create();
        $organization = Organization::withoutEvents(
            fn () => Organization::factory()->create(),
        );
        $event = Event::withoutEvents(fn () => Event::factory()->create([
            'organization_id' => $organization->id,
        ]));

        Sanctum::actingAs($admin);

        $this->postJson("/api/v1/events/{$event->id}/participants", [
            'type' => EventParticipantType::State->value,
            'name' => 'Selangor',
            'code' => 'SGR',
            'status' => EventParticipantStatus::Active->value,
        ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Selangor')
            ->assertJsonPath('data.code', 'SGR');

        $this->getJson("/api/v1/events/{$event->id}/participants")
            ->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    public function test_system_owner_can_show_and_update_participant_via_api(): void
    {
        $admin = User::factory()->admin()->create();
        $organization = Organization::withoutEvents(
            fn () => Organization::factory()->create(),
        );
        $event = Event::withoutEvents(fn () => Event::factory()->create([
            'organization_id' => $organization->id,
        ]));
        $participant = EventParticipant::withoutEvents(fn () => EventParticipant::factory()->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'name' => 'Johor',
            'code' => 'JHR',
        ]));

        Sanctum::actingAs($admin);

        $this->getJson("/api/v1/events/{$event->id}/participants/{$participant->id}")
            ->assertOk()
            ->assertJsonPath('data.name', 'Johor');

        $this->putJson("/api/v1/events/{$event->id}/participants/{$participant->id}", [
            'type' => EventParticipantType::State->value,
            'name' => 'Johor Darul Ta\'zim',
            'code' => 'JHR',
            'status' => EventParticipantStatus::Active->value,
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Johor Darul Ta\'zim');
    }

    public function test_system_owner_can_delete_participant_via_api(): void
    {
        $admin = User::factory()->admin()->create();
        $event = Event::withoutEvents(fn () => Event::factory()->create());
        $participant = EventParticipant::withoutEvents(fn () => EventParticipant::factory()->create([
            'organization_id' => $event->organization_id,
            'event_id' => $event->id,
        ]));

        Sanctum::actingAs($admin);

        $this->deleteJson("/api/v1/events/{$event->id}/participants/{$participant->id}")
            ->assertOk();

        $this->assertSoftDeleted('event_participants', ['id' => $participant->id]);
    }

    public function test_member_cannot_list_participants_via_api(): void
    {
        $event = Event::withoutEvents(fn () => Event::factory()->create());
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->getJson("/api/v1/events/{$event->id}/participants")->assertForbidden();
    }

    public function test_system_owner_can_manage_sport_entries_via_api(): void
    {
        $admin = User::factory()->admin()->create();
        $organization = Organization::withoutEvents(
            fn () => Organization::factory()->create(),
        );
        $event = Event::withoutEvents(fn () => Event::factory()->create([
            'organization_id' => $organization->id,
        ]));
        $participant = EventParticipant::withoutEvents(fn () => EventParticipant::factory()->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
        ]));
        $sport = Sport::withoutEvents(fn () => Sport::factory()->create([
            'event_id' => $event->id,
            'status' => SportStatus::Active,
        ]));

        Sanctum::actingAs($admin);

        $response = $this->postJson("/api/v1/events/{$event->id}/participants/{$participant->id}/entries", [
            'sport_id' => $sport->id,
            'status' => RegistrationStatus::Draft->value,
        ])
            ->assertCreated()
            ->assertJsonPath('data.sport_id', $sport->id);

        $entryId = $response->json('data.id');

        $this->getJson("/api/v1/events/{$event->id}/participants/{$participant->id}")
            ->assertOk()
            ->assertJsonPath('data.sport_entries.0.id', $entryId);

        $this->deleteJson("/api/v1/events/{$event->id}/participants/{$participant->id}/entries/{$entryId}")
            ->assertOk();

        $this->assertSoftDeleted('participant_sport_entries', ['id' => $entryId]);
    }

    public function test_duplicate_sport_entry_is_rejected_via_api(): void
    {
        $admin = User::factory()->admin()->create();
        $event = Event::withoutEvents(fn () => Event::factory()->create());
        $participant = EventParticipant::withoutEvents(fn () => EventParticipant::factory()->create([
            'organization_id' => $event->organization_id,
            'event_id' => $event->id,
        ]));
        $sport = Sport::withoutEvents(fn () => Sport::factory()->create([
            'event_id' => $event->id,
        ]));

        ParticipantSportEntry::create([
            'event_participant_id' => $participant->id,
            'sport_id' => $sport->id,
            'status' => RegistrationStatus::Draft,
        ]);

        Sanctum::actingAs($admin);

        $this->postJson("/api/v1/events/{$event->id}/participants/{$participant->id}/entries", [
            'sport_id' => $sport->id,
            'status' => RegistrationStatus::Draft->value,
        ])->assertUnprocessable();
    }

    public function test_system_owner_can_import_participants_from_csv_via_api(): void
    {
        $admin = User::factory()->admin()->create();
        $event = Event::withoutEvents(fn () => Event::factory()->create());

        $csv = implode("\n", [
            'type,name,code,status',
            'state,Selangor,SGR,active',
            'state,Johor,JHR,active',
        ]);

        $file = UploadedFile::fake()->createWithContent('participants.csv', $csv);

        Sanctum::actingAs($admin);

        $this->postJson("/api/v1/events/{$event->id}/participants/import", [
            'file' => $file,
        ])
            ->assertCreated()
            ->assertJsonPath('data.created', 2);

        $this->assertDatabaseHas('event_participants', [
            'event_id' => $event->id,
            'code' => 'SGR',
            'name' => 'Selangor',
        ]);

        $this->assertDatabaseHas('event_participants', [
            'event_id' => $event->id,
            'code' => 'JHR',
            'name' => 'Johor',
        ]);
    }

    public function test_csv_import_returns_validation_errors_for_invalid_rows(): void
    {
        $admin = User::factory()->admin()->create();
        $event = Event::withoutEvents(fn () => Event::factory()->create());

        EventParticipant::withoutEvents(fn () => EventParticipant::factory()->create([
            'organization_id' => $event->organization_id,
            'event_id' => $event->id,
            'code' => 'SGR',
        ]));

        $csv = implode("\n", [
            'type,name,code,status',
            'state,Selangor,SGR,active',
            'invalid,Bad Row,,active',
        ]);

        $file = UploadedFile::fake()->createWithContent('participants.csv', $csv);

        Sanctum::actingAs($admin);

        $this->postJson("/api/v1/events/{$event->id}/participants/import", [
            'file' => $file,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['rows.2.code', 'rows.3.type']);
    }
}