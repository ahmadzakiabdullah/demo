<?php

namespace Tests\Feature\Admin;

use App\Models\Competition;
use App\Models\CompetitionFormat;
use App\Models\Event;
use App\Models\Fixture;
use App\Models\MatchGame;
use App\Models\Organization;
use App\Models\Role;
use App\Models\Sport;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_org_admin_can_view_week_schedule(): void
    {
        $organization = Organization::withoutEvents(
            fn () => Organization::factory()->create(),
        );
        $event = Event::withoutEvents(fn () => Event::factory()->create([
            'organization_id' => $organization->id,
            'starts_at' => now()->startOfWeek(),
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
        $fixture = Fixture::withoutEvents(fn () => Fixture::factory()->create([
            'competition_id' => $competition->id,
        ]));
        $teams = Team::withoutEvents(fn () => Team::factory()->count(2)->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'sport_id' => $sport->id,
        ]));
        $match = MatchGame::withoutEvents(fn () => MatchGame::factory()->create([
            'fixture_id' => $fixture->id,
            'scheduled_at' => now()->startOfWeek()->addHours(10),
        ]));
        $match->participants()->createMany([
            [
                'participant_type' => Team::class,
                'participant_id' => $teams[0]->id,
                'side' => 'home',
            ],
            [
                'participant_type' => Team::class,
                'participant_id' => $teams[1]->id,
                'side' => 'away',
            ],
        ]);

        $orgAdminRoleId = Role::query()->where('slug', Role::ORG_ADMIN)->value('id');
        $orgAdmin = User::withoutEvents(fn () => User::factory()->create());
        $orgAdmin->organizations()->attach($organization->id, [
            'role_id' => $orgAdminRoleId,
            'status' => 'active',
        ]);

        $this->actingAs($orgAdmin)
            ->get(route('admin.events.schedule.index', $event))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Events/Schedule/Index')
                ->has('days', 7));
    }
}