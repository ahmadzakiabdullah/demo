<?php

namespace Tests\Feature\Admin;

use App\Enums\AppealStatus;
use App\Enums\RegistrationStatus;
use App\Enums\ResultStatus;
use App\Models\Competition;
use App\Models\CompetitionFormat;
use App\Models\Event;
use App\Models\MatchGame;
use App\Models\Organization;
use App\Models\Registration;
use App\Models\Result;
use App\Models\ResultAppeal;
use App\Models\Role;
use App\Models\Sport;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResultAppealTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{organization: Organization, event: Event, sport: Sport, competition: Competition, orgAdmin: User, teams: \Illuminate\Support\Collection<int, Team>, match: MatchGame, result: Result}
     */
    private function confirmedResultSetup(): array
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
        $teams = Team::withoutEvents(fn () => Team::factory()->count(2)->create([
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

        $match = MatchGame::query()->firstOrFail();
        $fixture = $match->fixture;

        $this->actingAs($orgAdmin)
            ->post(route('admin.events.competitions.matches.result.store', [
                $event,
                $competition,
                $fixture,
                $match,
            ]), [
                'home_score' => 2,
                'away_score' => 1,
            ]);

        $result = Result::query()->firstOrFail();

        $this->actingAs($orgAdmin)
            ->patch(route('admin.events.results.status', [$event, $result]), [
                'status' => ResultStatus::Confirmed->value,
            ]);

        return compact('organization', 'event', 'sport', 'competition', 'orgAdmin', 'teams', 'match', 'result');
    }

    public function test_team_manager_can_submit_appeal_on_confirmed_result(): void
    {
        $setup = $this->confirmedResultSetup();
        $team = $setup['teams']->first();
        $manager = User::withoutEvents(fn () => User::factory()->create());
        $team->update(['manager_user_id' => $manager->id]);

        $this->actingAs($manager)
            ->post(route('admin.events.results.appeals.store', [$setup['event'], $setup['result']]), [
                'reason' => 'Incorrect score recorded for our team.',
                'proposed_home_score' => 3,
                'proposed_away_score' => 1,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('result_appeals', [
            'result_id' => $setup['result']->id,
            'submitted_by' => $manager->id,
            'status' => AppealStatus::Submitted->value,
            'proposed_home_score' => 3,
            'proposed_away_score' => 1,
        ]);
    }

    public function test_org_admin_can_overturn_appeal_and_reset_result(): void
    {
        $setup = $this->confirmedResultSetup();

        $appeal = ResultAppeal::withoutEvents(fn () => ResultAppeal::factory()->create([
            'organization_id' => $setup['organization']->id,
            'result_id' => $setup['result']->id,
            'submitted_by' => $setup['teams']->first()->manager_user_id ?? $setup['orgAdmin']->id,
            'status' => AppealStatus::UnderReview,
            'proposed_home_score' => 3,
            'proposed_away_score' => 1,
        ]));

        $this->actingAs($setup['orgAdmin'])
            ->patch(route('admin.events.appeals.status', [$setup['event'], $appeal]), [
                'status' => AppealStatus::Overturned->value,
                'resolution_notes' => 'Video review supports the appeal.',
                'proposed_home_score' => 3,
                'proposed_away_score' => 1,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $setup['result']->refresh();

        $this->assertSame(AppealStatus::Overturned, $appeal->fresh()->status);
        $this->assertSame(ResultStatus::Pending, $setup['result']->status);
        $this->assertSame(3, $setup['result']->data['home_score']);
        $this->assertSame(1, $setup['result']->data['away_score']);
        $this->assertNull($setup['result']->confirmed_at);
    }

    public function test_cannot_submit_appeal_on_pending_result(): void
    {
        $setup = $this->confirmedResultSetup();
        $result = $setup['result']->fresh();
        $result->update([
            'status' => ResultStatus::Pending,
            'confirmed_by' => null,
            'confirmed_at' => null,
            'published_at' => null,
        ]);

        $this->assertSame(ResultStatus::Pending, $result->fresh()->status);

        $this->actingAs($setup['orgAdmin'])
            ->post(route('admin.events.results.appeals.store', [$setup['event'], $result]), [
                'reason' => 'This should not be allowed yet.',
                'proposed_home_score' => 1,
                'proposed_away_score' => 0,
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('result_appeals', 0);
    }
}