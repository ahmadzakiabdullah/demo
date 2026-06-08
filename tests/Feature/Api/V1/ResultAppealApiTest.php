<?php

namespace Tests\Feature\Api\V1;

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
use App\Models\Role;
use App\Models\Sport;
use App\Models\Team;
use App\Models\User;
use App\Support\DrawGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ResultAppealApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_org_admin_can_submit_and_resolve_appeal_via_api(): void
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

        app(DrawGenerator::class)->generate($competition);
        $match = MatchGame::query()->firstOrFail();

        $orgAdminRoleId = Role::query()->where('slug', Role::ORG_ADMIN)->value('id');
        $orgAdmin = User::withoutEvents(fn () => User::factory()->create());
        $orgAdmin->organizations()->attach($organization->id, [
            'role_id' => $orgAdminRoleId,
            'status' => 'active',
        ]);

        Sanctum::actingAs($orgAdmin);

        $this->postJson("/api/v1/events/{$event->id}/matches/{$match->id}/result", [
            'home_score' => 1,
            'away_score' => 0,
        ])->assertCreated();

        $result = Result::query()->firstOrFail();

        $this->patchJson("/api/v1/results/{$result->id}/status", [
            'status' => ResultStatus::Confirmed->value,
        ])->assertOk();

        $this->postJson("/api/v1/results/{$result->id}/appeals", [
            'reason' => 'Score does not match the official sheet.',
            'proposed_home_score' => 2,
            'proposed_away_score' => 0,
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', AppealStatus::Submitted->value);

        $appealId = $result->fresh()->appeals()->firstOrFail()->id;

        $this->patchJson("/api/v1/appeals/{$appealId}/status", [
            'status' => AppealStatus::Upheld->value,
            'resolution_notes' => 'Original score verified.',
        ])
            ->assertOk()
            ->assertJsonPath('data.status', AppealStatus::Upheld->value);
    }
}