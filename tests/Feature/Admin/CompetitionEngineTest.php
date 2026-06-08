<?php

namespace Tests\Feature\Admin;

use App\Enums\RegistrationStatus;
use App\Enums\ResultStatus;
use App\Models\Competition;
use App\Models\CompetitionFormat;
use App\Models\Event;
use App\Models\MatchGame;
use App\Models\Organization;
use App\Models\Registration;
use App\Models\Result;
use App\Models\Role;
use App\Models\Sport;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompetitionEngineTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{organization: Organization, event: Event, sport: Sport, competition: Competition, orgAdmin: User, teams: \Illuminate\Support\Collection<int, Team>}
     */
    private function leagueSetup(): array
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
        $competition = Competition::withoutEvents(fn () => Competition::factory()->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'sport_id' => $sport->id,
            'competition_format_id' => $format->id,
        ]));
        $teams = Team::withoutEvents(fn () => Team::factory()->count(3)->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'sport_id' => $sport->id,
        ]));

        foreach ($teams as $team) {
            Registration::withoutEvents(fn () => Registration::factory()->create([
                'event_id' => $event->id,
                'sport_id' => $sport->id,
                'registrable_type' => Team::class,
                'registrable_id' => $team->id,
                'status' => RegistrationStatus::Approved,
            ]));
        }

        $orgAdminRoleId = Role::query()->where('slug', Role::ORG_ADMIN)->value('id');
        $orgAdmin = User::withoutEvents(fn () => User::factory()->create());
        $orgAdmin->organizations()->attach($organization->id, [
            'role_id' => $orgAdminRoleId,
            'status' => 'active',
        ]);

        return compact('organization', 'event', 'sport', 'competition', 'orgAdmin', 'teams');
    }

    public function test_org_admin_can_generate_round_robin_draw(): void
    {
        $setup = $this->leagueSetup();

        $this->actingAs($setup['orgAdmin'])
            ->post(route('admin.events.competitions.draw', [$setup['event'], $setup['competition']]))
            ->assertRedirect();

        $this->assertDatabaseCount('fixtures', 1);
        $this->assertDatabaseCount('matches', 3);
    }

    public function test_confirming_result_updates_rankings_and_medals(): void
    {
        $setup = $this->leagueSetup();

        $this->actingAs($setup['orgAdmin'])
            ->post(route('admin.events.competitions.draw', [$setup['event'], $setup['competition']]));

        $match = MatchGame::query()->firstOrFail();
        $fixture = $match->fixture;

        $this->actingAs($setup['orgAdmin'])
            ->post(route('admin.events.competitions.matches.result.store', [
                $setup['event'],
                $setup['competition'],
                $fixture,
                $match,
            ]), [
                'home_score' => 2,
                'away_score' => 1,
            ])
            ->assertRedirect();

        $result = Result::query()->firstOrFail();

        $this->actingAs($setup['orgAdmin'])
            ->patch(route('admin.events.results.status', [$setup['event'], $result]), [
                'status' => ResultStatus::Confirmed->value,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('rankings', [
            'competition_id' => $setup['competition']->id,
            'points' => 3,
        ]);
    }

    public function test_knockout_draw_advances_winner_to_next_round(): void
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
        $competition = Competition::withoutEvents(fn () => Competition::factory()->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'sport_id' => $sport->id,
            'competition_format_id' => $format->id,
        ]));
        $teams = Team::withoutEvents(fn () => Team::factory()->count(4)->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'sport_id' => $sport->id,
        ]));

        foreach ($teams as $team) {
            Registration::withoutEvents(fn () => Registration::factory()->create([
                'event_id' => $event->id,
                'sport_id' => $sport->id,
                'registrable_type' => Team::class,
                'registrable_id' => $team->id,
                'status' => RegistrationStatus::Approved,
            ]));
        }

        $orgAdminRoleId = Role::query()->where('slug', Role::ORG_ADMIN)->value('id');
        $orgAdmin = User::withoutEvents(fn () => User::factory()->create());
        $orgAdmin->organizations()->attach($organization->id, [
            'role_id' => $orgAdminRoleId,
            'status' => 'active',
        ]);

        $this->actingAs($orgAdmin)
            ->post(route('admin.events.competitions.draw', [$event, $competition]));

        $semiMatch = MatchGame::query()->orderBy('id')->firstOrFail();
        $fixture = $semiMatch->fixture;

        $this->actingAs($orgAdmin)
            ->post(route('admin.events.competitions.matches.result.store', [
                $event,
                $competition,
                $fixture,
                $semiMatch,
            ]), [
                'home_score' => 3,
                'away_score' => 0,
            ]);

        $result = Result::query()->firstOrFail();

        $this->actingAs($orgAdmin)
            ->patch(route('admin.events.results.status', [$event, $result]), [
                'status' => ResultStatus::Confirmed->value,
            ]);

        $homeParticipant = $semiMatch->participants()->where('side', 'home')->firstOrFail();

        $this->assertDatabaseHas('match_participants', [
            'match_id' => $semiMatch->fresh()->winner_advances_to_match_id,
            'participant_id' => $homeParticipant->participant_id,
        ]);
    }
}