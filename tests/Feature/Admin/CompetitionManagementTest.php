<?php

namespace Tests\Feature\Admin;

use App\Enums\RegistrationStatus;
use App\Models\Athlete;
use App\Models\Competition;
use App\Models\CompetitionFormat;
use App\Models\Event;
use App\Models\Official;
use App\Models\Organization;
use App\Models\Registration;
use App\Models\Role;
use App\Models\Sport;
use App\Models\Team;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompetitionManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_access_competitions(): void
    {
        $event = Event::withoutEvents(fn () => Event::factory()->create());

        $this->get(route('admin.events.competitions.index', $event))
            ->assertRedirect(route('login'));
    }

    public function test_org_admin_can_create_competition_with_fixture_and_match(): void
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
        $format = CompetitionFormat::query()->where('slug', 'round_robin')->firstOrFail();
        $venue = Venue::withoutEvents(fn () => Venue::factory()->create([
            'organization_id' => $organization->id,
        ]));
        $event->venues()->attach($venue->id);
        $homeTeam = Team::withoutEvents(fn () => Team::factory()->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'sport_id' => $sport->id,
            'name' => 'Home FC',
        ]));
        $awayTeam = Team::withoutEvents(fn () => Team::factory()->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'sport_id' => $sport->id,
            'name' => 'Away FC',
        ]));
        $official = Official::withoutEvents(fn () => Official::factory()->create([
            'organization_id' => $organization->id,
        ]));
        Registration::withoutEvents(fn () => Registration::factory()->create([
            'event_id' => $event->id,
            'sport_id' => $sport->id,
            'registrable_type' => Official::class,
            'registrable_id' => $official->id,
            'status' => RegistrationStatus::Approved,
        ]));

        $orgAdminRoleId = Role::query()->where('slug', Role::ORG_ADMIN)->value('id');
        $orgAdmin = User::withoutEvents(fn () => User::factory()->create());
        $orgAdmin->organizations()->attach($organization->id, [
            'role_id' => $orgAdminRoleId,
            'status' => 'active',
        ]);

        $this->actingAs($orgAdmin)
            ->post(route('admin.events.competitions.store', $event), [
                'sport_id' => $sport->id,
                'competition_format_id' => $format->id,
                'name' => 'Men Open League',
            ])
            ->assertRedirect();

        $competition = Competition::query()->firstOrFail();

        $this->actingAs($orgAdmin)
            ->post(route('admin.events.competitions.fixtures.store', [$event, $competition]), [
                'name' => 'Match Day 1',
                'round' => 'Round 1',
            ])
            ->assertRedirect();

        $fixture = $competition->fixtures()->firstOrFail();
        $scheduledAt = now()->addDay()->format('Y-m-d H:i:s');

        $this->actingAs($orgAdmin)
            ->post(route('admin.events.competitions.matches.store', [$event, $competition, $fixture]), [
                'scheduled_at' => $scheduledAt,
                'duration_minutes' => 90,
                'venue_id' => $venue->id,
                'participants' => [
                    [
                        'side' => 'home',
                        'participant_type' => Team::class,
                        'participant_id' => $homeTeam->id,
                    ],
                    [
                        'side' => 'away',
                        'participant_type' => Team::class,
                        'participant_id' => $awayTeam->id,
                    ],
                ],
                'officials' => [
                    ['official_id' => $official->id, 'role' => 'referee'],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('matches', [
            'fixture_id' => $fixture->id,
            'venue_id' => $venue->id,
        ]);
        $this->assertDatabaseCount('match_participants', 2);
        $this->assertDatabaseHas('match_officials', [
            'official_id' => $official->id,
        ]);
    }

    public function test_venue_conflict_blocks_overlapping_match(): void
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
        $format = CompetitionFormat::query()->where('slug', 'knockout')->firstOrFail();
        $venue = Venue::withoutEvents(fn () => Venue::factory()->create([
            'organization_id' => $organization->id,
        ]));
        $event->venues()->attach($venue->id);
        $teams = Team::withoutEvents(fn () => Team::factory()->count(4)->create([
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

        $competition = Competition::withoutEvents(fn () => Competition::factory()->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'sport_id' => $sport->id,
            'competition_format_id' => $format->id,
        ]));
        $fixture = $competition->fixtures()->create([
            'name' => 'Semi-finals',
            'round' => 'Semi-final',
        ]);

        $scheduledAt = now()->addDays(2)->format('Y-m-d H:i:s');
        $payload = [
            'scheduled_at' => $scheduledAt,
            'duration_minutes' => 60,
            'venue_id' => $venue->id,
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
        ];

        $this->actingAs($orgAdmin)
            ->post(route('admin.events.competitions.matches.store', [$event, $competition, $fixture]), $payload)
            ->assertRedirect();

        $this->actingAs($orgAdmin)
            ->post(route('admin.events.competitions.matches.store', [$event, $competition, $fixture]), [
                ...$payload,
                'participants' => [
                    [
                        'side' => 'home',
                        'participant_type' => Team::class,
                        'participant_id' => $teams[2]->id,
                    ],
                    [
                        'side' => 'away',
                        'participant_type' => Team::class,
                        'participant_id' => $teams[3]->id,
                    ],
                ],
            ])
            ->assertSessionHasErrors('venue_id');
    }

    public function test_athlete_conflict_blocks_double_booking(): void
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
        $format = CompetitionFormat::query()->where('slug', 'league')->firstOrFail();
        $athlete = Athlete::withoutEvents(fn () => Athlete::factory()->create([
            'organization_id' => $organization->id,
        ]));
        $opponents = Athlete::withoutEvents(fn () => Athlete::factory()->count(3)->create([
            'organization_id' => $organization->id,
        ]));

        $orgAdminRoleId = Role::query()->where('slug', Role::ORG_ADMIN)->value('id');
        $orgAdmin = User::withoutEvents(fn () => User::factory()->create());
        $orgAdmin->organizations()->attach($organization->id, [
            'role_id' => $orgAdminRoleId,
            'status' => 'active',
        ]);

        $competition = Competition::withoutEvents(fn () => Competition::factory()->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'sport_id' => $sport->id,
            'competition_format_id' => $format->id,
        ]));
        $fixture = $competition->fixtures()->create(['name' => 'Singles Day 1']);

        $scheduledAt = now()->addDays(3)->format('Y-m-d H:i:s');

        $this->actingAs($orgAdmin)
            ->post(route('admin.events.competitions.matches.store', [$event, $competition, $fixture]), [
                'scheduled_at' => $scheduledAt,
                'duration_minutes' => 45,
                'participants' => [
                    [
                        'side' => 'home',
                        'participant_type' => Athlete::class,
                        'participant_id' => $athlete->id,
                    ],
                    [
                        'side' => 'away',
                        'participant_type' => Athlete::class,
                        'participant_id' => $opponents[0]->id,
                    ],
                ],
            ])
            ->assertRedirect();

        $this->actingAs($orgAdmin)
            ->post(route('admin.events.competitions.matches.store', [$event, $competition, $fixture]), [
                'scheduled_at' => $scheduledAt,
                'duration_minutes' => 45,
                'participants' => [
                    [
                        'side' => 'home',
                        'participant_type' => Athlete::class,
                        'participant_id' => $athlete->id,
                    ],
                    [
                        'side' => 'away',
                        'participant_type' => Athlete::class,
                        'participant_id' => $opponents[1]->id,
                    ],
                ],
            ])
            ->assertSessionHasErrors('participants');
    }

    public function test_member_cannot_create_competition(): void
    {
        $event = Event::withoutEvents(fn () => Event::factory()->create());
        $sport = Sport::withoutEvents(fn () => Sport::factory()->create([
            'event_id' => $event->id,
        ]));
        $format = CompetitionFormat::query()->firstOrFail();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.events.competitions.store', $event), [
                'sport_id' => $sport->id,
                'competition_format_id' => $format->id,
                'name' => 'Blocked Cup',
            ])
            ->assertForbidden();
    }
}