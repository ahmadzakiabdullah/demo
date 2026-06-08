<?php

namespace Tests\Feature\Admin;

use App\Enums\RegistrationStatus;
use App\Enums\ResultStatus;
use App\Events\ResultScoreUpdated;
use App\Models\Competition;
use App\Models\CompetitionFormat;
use App\Models\CompetitionGroup;
use App\Models\Event;
use App\Models\MatchGame;
use App\Models\MedalCeremony;
use App\Models\Organization;
use App\Models\Registration;
use App\Models\Result;
use App\Models\Role;
use App\Models\Sport;
use App\Models\Team;
use App\Models\User;
use App\Support\DrawGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event as EventFacade;
use Tests\TestCase;

class Phase3CompletionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{organization: Organization, event: Event, sport: Sport, orgAdmin: User}
     */
    private function baseSetup(): array
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
        $orgAdminRoleId = Role::query()->where('slug', Role::ORG_ADMIN)->value('id');
        $orgAdmin = User::withoutEvents(fn () => User::factory()->create());
        $orgAdmin->organizations()->attach($organization->id, [
            'role_id' => $orgAdminRoleId,
            'status' => 'active',
        ]);

        return compact('organization', 'event', 'sport', 'orgAdmin');
    }

    /**
     * @return \Illuminate\Support\Collection<int, Team>
     */
    private function registerTeams(Event $event, Sport $sport, Organization $organization, int $count): \Illuminate\Support\Collection
    {
        $teams = Team::withoutEvents(fn () => Team::factory()->count($count)->create([
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

        return $teams;
    }

    public function test_double_elimination_draw_creates_winners_losers_and_grand_final(): void
    {
        $setup = $this->baseSetup();
        $format = CompetitionFormat::query()->where('slug', 'double_elimination')->firstOrFail();
        $competition = Competition::withoutEvents(fn () => Competition::factory()->create([
            'organization_id' => $setup['organization']->id,
            'event_id' => $setup['event']->id,
            'sport_id' => $setup['sport']->id,
            'competition_format_id' => $format->id,
        ]));
        $this->registerTeams($setup['event'], $setup['sport'], $setup['organization'], 4);

        $this->actingAs($setup['orgAdmin'])
            ->post(route('admin.events.competitions.draw', [$setup['event'], $competition]))
            ->assertRedirect();

        $this->assertDatabaseHas('matches', ['bracket_lane' => 'winners']);
        $this->assertDatabaseHas('matches', ['bracket_lane' => 'losers']);
        $this->assertDatabaseHas('matches', ['bracket_lane' => 'grand_final']);
    }

    public function test_swiss_draw_creates_round_fixtures(): void
    {
        $setup = $this->baseSetup();
        $format = CompetitionFormat::query()->where('slug', 'swiss')->firstOrFail();
        $competition = Competition::withoutEvents(fn () => Competition::factory()->create([
            'organization_id' => $setup['organization']->id,
            'event_id' => $setup['event']->id,
            'sport_id' => $setup['sport']->id,
            'competition_format_id' => $format->id,
            'settings' => ['swiss_rounds' => 3],
        ]));
        $this->registerTeams($setup['event'], $setup['sport'], $setup['organization'], 4);

        app(DrawGenerator::class)->generate($competition);

        $this->assertDatabaseCount('fixtures', 3);
        $this->assertDatabaseHas('matches', ['bracket_lane' => 'swiss']);
    }

    public function test_ladder_draw_creates_challenge_match(): void
    {
        $setup = $this->baseSetup();
        $format = CompetitionFormat::query()->where('slug', 'ladder')->firstOrFail();
        $competition = Competition::withoutEvents(fn () => Competition::factory()->create([
            'organization_id' => $setup['organization']->id,
            'event_id' => $setup['event']->id,
            'sport_id' => $setup['sport']->id,
            'competition_format_id' => $format->id,
        ]));
        $this->registerTeams($setup['event'], $setup['sport'], $setup['organization'], 3);

        app(DrawGenerator::class)->generate($competition);

        $this->assertDatabaseCount('matches', 1);
        $this->assertDatabaseHas('matches', ['bracket_lane' => 'ladder']);
    }

    public function test_group_stage_can_generate_knockout_phase(): void
    {
        $setup = $this->baseSetup();
        $format = CompetitionFormat::query()->where('slug', 'group_stage')->firstOrFail();
        $competition = Competition::withoutEvents(fn () => Competition::factory()->create([
            'organization_id' => $setup['organization']->id,
            'event_id' => $setup['event']->id,
            'sport_id' => $setup['sport']->id,
            'competition_format_id' => $format->id,
            'settings' => ['group_advance_count' => 1],
        ]));
        CompetitionGroup::query()->create([
            'competition_id' => $competition->id,
            'name' => 'Group A',
            'slug' => 'group-a',
            'sort_order' => 0,
        ]);
        CompetitionGroup::query()->create([
            'competition_id' => $competition->id,
            'name' => 'Group B',
            'slug' => 'group-b',
            'sort_order' => 1,
        ]);
        $this->registerTeams($setup['event'], $setup['sport'], $setup['organization'], 4);

        app(DrawGenerator::class)->generate($competition);

        foreach (MatchGame::query()->get() as $match) {
            $fixture = $match->fixture;
            $this->actingAs($setup['orgAdmin'])
                ->post(route('admin.events.competitions.matches.result.store', [
                    $setup['event'],
                    $competition,
                    $fixture,
                    $match,
                ]), ['home_score' => 1, 'away_score' => 0]);

            $result = Result::query()->where('match_id', $match->id)->firstOrFail();
            $this->actingAs($setup['orgAdmin'])
                ->patch(route('admin.events.results.status', [$setup['event'], $result]), [
                    'status' => ResultStatus::Confirmed->value,
                ]);
        }

        app(DrawGenerator::class)->generateKnockoutPhase($competition);

        $this->assertDatabaseHas('fixtures', ['round' => 'Knockout']);
    }

    public function test_result_confirmation_broadcasts_live_update_event(): void
    {
        EventFacade::fake([ResultScoreUpdated::class]);

        $setup = $this->baseSetup();
        $format = CompetitionFormat::query()->where('slug', 'round_robin')->firstOrFail();
        $competition = Competition::withoutEvents(fn () => Competition::factory()->create([
            'organization_id' => $setup['organization']->id,
            'event_id' => $setup['event']->id,
            'sport_id' => $setup['sport']->id,
            'competition_format_id' => $format->id,
        ]));
        $this->registerTeams($setup['event'], $setup['sport'], $setup['organization'], 2);
        app(DrawGenerator::class)->generate($competition);
        $match = MatchGame::query()->firstOrFail();

        $this->actingAs($setup['orgAdmin'])
            ->post(route('admin.events.competitions.matches.result.store', [
                $setup['event'],
                $competition,
                $match->fixture,
                $match,
            ]), ['home_score' => 2, 'away_score' => 1]);

        EventFacade::assertDispatched(ResultScoreUpdated::class);
    }

    public function test_org_admin_can_schedule_medal_ceremony(): void
    {
        $setup = $this->baseSetup();

        $this->actingAs($setup['orgAdmin'])
            ->post(route('admin.events.medal-ceremonies.store', $setup['event']), [
                'name' => 'Closing Ceremony',
                'scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
                'duration_minutes' => 90,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('medal_ceremonies', [
            'event_id' => $setup['event']->id,
            'name' => 'Closing Ceremony',
        ]);
    }
}