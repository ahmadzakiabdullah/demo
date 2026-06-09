<?php

namespace Tests\Feature\Admin;

use App\Enums\EventCadence;
use App\Enums\EventParticipantType;
use App\Enums\EventStatus;
use App\Enums\ParticipantUnitLabel;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\EventParticipant;
use App\Models\EventType;
use App\Models\Organization;
use App\Models\Sport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class EventParticipantManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_owner_can_register_event_participant(): void
    {
        $admin = User::factory()->admin()->create();
        $organization = Organization::withoutEvents(
            fn () => Organization::factory()->create(),
        );

        $event = Event::withoutEvents(fn () => Event::factory()->create([
            'organization_id' => $organization->id,
            'edition_year' => 2026,
            'cadence' => EventCadence::Biennial,
            'participant_unit_label' => ParticipantUnitLabel::State,
        ]));

        Sport::factory()->create(['event_id' => $event->id]);

        $this->actingAs($admin)
            ->post(route('admin.events.participants.store', $event), [
                'type' => EventParticipantType::State->value,
                'name' => 'Selangor',
                'code' => 'SGR',
                'status' => 'active',
            ])
            ->assertRedirect();

        $participant = EventParticipant::query()->where('code', 'SGR')->first();
        $this->assertNotNull($participant);
        $this->assertSame($event->id, $participant->event_id);
        $this->assertSame($organization->id, $participant->organization_id);
    }

    public function test_event_store_requires_edition_year(): void
    {
        $admin = User::factory()->admin()->create();
        $organization = Organization::withoutEvents(
            fn () => Organization::factory()->create(),
        );

        $this->actingAs($admin)
            ->post(route('admin.events.store'), [
                'organization_id' => $organization->id,
                'event_type_id' => EventType::query()->first()->id,
                'event_category_id' => EventCategory::query()->first()->id,
                'name' => 'Test Games',
                'status' => EventStatus::Draft->value,
            ])
            ->assertSessionHasErrors('edition_year');
    }

    public function test_system_owner_can_view_import_form(): void
    {
        $admin = User::factory()->admin()->create();
        $event = Event::withoutEvents(fn () => Event::factory()->create([
            'participant_unit_label' => ParticipantUnitLabel::State,
        ]));

        $this->actingAs($admin)
            ->get(route('admin.events.participants.import', $event))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Events/Participants/Import')
                ->where('participantUnitLabel', 'State'));
    }

    public function test_system_owner_can_import_participants_from_csv(): void
    {
        $admin = User::factory()->admin()->create();
        $event = Event::withoutEvents(fn () => Event::factory()->create([
            'participant_unit_label' => ParticipantUnitLabel::State,
        ]));

        $csv = implode("\n", [
            'type,name,code,status',
            'state,Selangor,SGR,active',
            'state,Johor,JHR,active',
        ]);

        $file = UploadedFile::fake()->createWithContent('participants.csv', $csv);

        $this->actingAs($admin)
            ->post(route('admin.events.participants.import.store', $event), [
                'file' => $file,
            ])
            ->assertRedirect(route('admin.events.participants.index', $event))
            ->assertSessionHas('success');

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

    public function test_csv_import_returns_validation_errors(): void
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

        $this->actingAs($admin)
            ->post(route('admin.events.participants.import.store', $event), [
                'file' => $file,
            ])
            ->assertRedirect(route('admin.events.participants.import', $event))
            ->assertSessionHasErrors(['rows.2.code', 'rows.3.type']);
    }

    public function test_system_owner_can_download_import_template(): void
    {
        $admin = User::factory()->admin()->create();
        $event = Event::withoutEvents(fn () => Event::factory()->create([
            'participant_unit_label' => ParticipantUnitLabel::State,
        ]));

        $this->actingAs($admin)
            ->get(route('admin.events.participants.import.template', $event))
            ->assertOk()
            ->assertHeader('content-disposition');
    }

    public function test_events_index_sorts_by_edition_year(): void
    {
        $admin = User::factory()->admin()->create();
        $organization = Organization::withoutEvents(
            fn () => Organization::factory()->create(),
        );

        Event::withoutEvents(fn () => Event::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Older Games',
            'slug' => 'older-games',
            'edition_year' => 2024,
        ]));

        Event::withoutEvents(fn () => Event::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Newer Games',
            'slug' => 'newer-games',
            'edition_year' => 2026,
        ]));

        $this->actingAs($admin)
            ->get(route('admin.events.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Events/Index')
                ->where('events.data.0.name', 'Newer Games'));
    }
}