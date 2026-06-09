<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CompetitionStatus;
use App\Enums\MatchOfficialRole;
use App\Enums\MatchParticipantSide;
use App\Enums\MatchStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCompetitionGroupRequest;
use App\Http\Requests\Admin\StoreCompetitionRequest;
use App\Http\Requests\Admin\StoreFixtureRequest;
use App\Http\Requests\Admin\StoreMatchRequest;
use App\Http\Requests\Admin\UpdateCompetitionRequest;
use App\Http\Requests\Admin\UpdateMatchRequest;
use App\Models\Athlete;
use App\Models\Competition;
use App\Models\CompetitionFormat;
use App\Models\CompetitionGroup;
use App\Models\Event;
use App\Models\Fixture;
use App\Models\MatchGame;
use App\Models\Official;
use App\Models\Team;
use App\Support\DrawGenerator;
use App\Support\MatchScheduler;
use App\Support\ScheduleConflictDetector;
use Illuminate\Http\RedirectResponse;
use RuntimeException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CompetitionController extends Controller
{
    public function index(Request $request, Event $event): Response
    {
        $this->authorize('view', $event);
        $this->authorize('viewAny', Competition::class);

        $competitions = $event->competitions()
            ->with([
                'sport:id,name,slug',
                'format:id,name,slug',
            ])
            ->withCount('fixtures')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();
                $query->where(function ($builder) use ($search) {
                    $builder
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('sport_id'), fn ($query) => $query->where('sport_id', $request->integer('sport_id')))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Competition $competition) => [
                'id' => $competition->id,
                'name' => $competition->name,
                'slug' => $competition->slug,
                'status' => $competition->status->value,
                'sport' => $competition->sport?->only(['id', 'name', 'slug']),
                'format' => $competition->format?->only(['id', 'name', 'slug']),
                'fixtures_count' => $competition->fixtures_count,
            ]);

        return Inertia::render('Admin/Events/Competitions/Index', [
            'event' => $event->only(['id', 'name', 'slug']),
            'competitions' => $competitions,
            'sports' => $event->sports()->orderBy('name')->get(['id', 'name', 'slug']),
            'statuses' => CompetitionStatus::values(),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'status' => $request->string('status')->toString(),
                'sport_id' => $request->string('sport_id')->toString(),
            ],
        ]);
    }

    public function create(Event $event): Response
    {
        $this->authorize('create', [Competition::class, $event]);

        return Inertia::render('Admin/Events/Competitions/Create', [
            'event' => $event->only(['id', 'name', 'slug']),
            'sports' => $event->sports()->orderBy('name')->get(['id', 'name', 'slug']),
            'formats' => CompetitionFormat::query()->orderBy('sort_order')->get(['id', 'name', 'slug', 'description']),
            'statuses' => CompetitionStatus::values(),
        ]);
    }

    public function store(StoreCompetitionRequest $request, Event $event): RedirectResponse
    {
        $validated = $request->validated();
        $slug = $validated['slug'] ?? $this->uniqueSlug(
            $validated['name'],
            $event->id,
            (int) $validated['sport_id'],
        );

        $competition = Competition::query()->create([
            'organization_id' => $event->organization_id,
            'event_id' => $event->id,
            'sport_id' => $validated['sport_id'],
            'competition_format_id' => $validated['competition_format_id'],
            'name' => $validated['name'],
            'slug' => $slug,
            'status' => $validated['status'] ?? CompetitionStatus::Draft->value,
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()
            ->route('admin.events.competitions.show', [$event, $competition])
            ->with('success', 'Competition created.');
    }

    public function show(Event $event, Competition $competition): Response
    {
        $this->ensureBelongsToEvent($event, $competition);
        $this->authorize('view', $competition);

        $competition->load([
            'sport:id,name,slug',
            'format:id,name,slug',
            'rankings.rankable',
            'groups' => fn ($query) => $query->orderBy('sort_order')->orderBy('name'),
            'fixtures' => fn ($query) => $query
                ->with([
                    'group:id,name,slug',
                    'matches' => fn ($matches) => $matches
                        ->with([
                            'venue:id,name',
                            'facility:id,name',
                            'participants.participant',
                            'officials.official:id,name',
                            'result.appeals.submittedBy:id,name',
                        ])
                        ->orderBy('scheduled_at'),
                ])
                ->orderBy('sort_order')
                ->orderBy('name'),
        ]);

        return Inertia::render('Admin/Events/Competitions/Show', [
            'event' => $event->only(['id', 'name', 'slug']),
            'competition' => $this->competitionPayload($competition),
            'venues' => $this->venueOptions($event, $competition->sport_id),
            'teams' => $this->teamOptions($event, $competition->sport_id),
            'athletes' => $this->athleteOptions($event),
            'officials' => $this->officialOptions($event),
            'participantSides' => MatchParticipantSide::values(),
            'officialRoles' => MatchOfficialRole::values(),
            'matchStatuses' => MatchStatus::values(),
            'canManageSchedule' => request()->user()?->can('manageSchedule', $competition) ?? false,
            'resultStatuses' => \App\Enums\ResultStatus::values(),
            'appealStatuses' => \App\Enums\AppealStatus::values(),
            'bracket' => $this->bracketPayload($competition),
            'scoreSchema' => \App\Support\ScoreSchema::forSport($competition->sport),
            'seedingStrategies' => \App\Enums\SeedingStrategy::values(),
            'supportsKnockoutPhase' => $competition->format?->supportsKnockoutPhase() ?? false,
        ]);
    }

    public function generateDraw(Event $event, Competition $competition): RedirectResponse
    {
        $this->ensureBelongsToEvent($event, $competition);
        $this->authorize('manageSchedule', $competition);

        try {
            $stats = app(DrawGenerator::class)->generate($competition);

            return back()->with(
                'success',
                "Draw generated: {$stats['matches']} matches for {$stats['participants']} participants.",
            );
        } catch (RuntimeException $exception) {
            return back()->withErrors(['draw' => $exception->getMessage()]);
        }
    }

    public function generateKnockoutPhase(Event $event, Competition $competition): RedirectResponse
    {
        $this->ensureBelongsToEvent($event, $competition);
        $this->authorize('manageSchedule', $competition);

        try {
            $stats = app(DrawGenerator::class)->generateKnockoutPhase($competition);

            return back()->with(
                'success',
                "Knockout phase generated: {$stats['matches']} matches for {$stats['participants']} qualifiers.",
            );
        } catch (RuntimeException $exception) {
            return back()->withErrors(['draw' => $exception->getMessage()]);
        }
    }

    public function edit(Event $event, Competition $competition): Response
    {
        $this->ensureBelongsToEvent($event, $competition);
        $this->authorize('update', $competition);

        $competition->load(['sport:id,name,slug', 'format:id,name,slug']);

        return Inertia::render('Admin/Events/Competitions/Edit', [
            'event' => $event->only(['id', 'name', 'slug']),
            'competition' => $this->competitionPayload($competition),
            'formats' => CompetitionFormat::query()->orderBy('sort_order')->get(['id', 'name', 'slug', 'description']),
            'statuses' => CompetitionStatus::values(),
        ]);
    }

    public function update(UpdateCompetitionRequest $request, Event $event, Competition $competition): RedirectResponse
    {
        $this->ensureBelongsToEvent($event, $competition);

        $validated = $request->validated();

        if (array_key_exists('slug', $validated) && empty($validated['slug'])) {
            unset($validated['slug']);
        }

        $competition->update($validated);

        return redirect()
            ->route('admin.events.competitions.show', [$event, $competition])
            ->with('success', 'Competition updated.');
    }

    public function destroy(Event $event, Competition $competition): RedirectResponse
    {
        $this->ensureBelongsToEvent($event, $competition);
        $this->authorize('delete', $competition);

        $competition->delete();

        return redirect()
            ->route('admin.events.competitions.index', $event)
            ->with('success', 'Competition deleted.');
    }

    public function storeGroup(StoreCompetitionGroupRequest $request, Event $event, Competition $competition): RedirectResponse
    {
        $this->ensureBelongsToEvent($event, $competition);

        $validated = $request->validated();
        $slug = $validated['slug'] ?? Str::slug($validated['name']);

        CompetitionGroup::query()->create([
            'competition_id' => $competition->id,
            'name' => $validated['name'],
            'slug' => $slug,
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return back()->with('success', 'Group added.');
    }

    public function storeFixture(StoreFixtureRequest $request, Event $event, Competition $competition): RedirectResponse
    {
        $this->ensureBelongsToEvent($event, $competition);

        $validated = $request->validated();

        Fixture::query()->create([
            'competition_id' => $competition->id,
            'group_id' => $validated['group_id'] ?? null,
            'name' => $validated['name'],
            'round' => $validated['round'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return back()->with('success', 'Fixture added.');
    }

    public function destroyFixture(Event $event, Competition $competition, Fixture $fixture): RedirectResponse
    {
        $this->ensureBelongsToEvent($event, $competition);
        $this->authorize('manageSchedule', $competition);
        $this->ensureFixtureBelongsToCompetition($competition, $fixture);

        $fixture->delete();

        return back()->with('success', 'Fixture removed.');
    }

    public function storeMatch(StoreMatchRequest $request, Event $event, Competition $competition, Fixture $fixture): RedirectResponse
    {
        $this->ensureBelongsToEvent($event, $competition);
        $this->ensureFixtureBelongsToCompetition($competition, $fixture);

        app(MatchScheduler::class)->create($fixture, $request->validated());

        return back()->with('success', 'Match scheduled.');
    }

    public function updateMatch(UpdateMatchRequest $request, Event $event, Competition $competition, Fixture $fixture, MatchGame $matchGame): RedirectResponse
    {
        $this->ensureBelongsToEvent($event, $competition);
        $this->ensureFixtureBelongsToCompetition($competition, $fixture);
        $this->ensureMatchBelongsToFixture($fixture, $matchGame);

        app(MatchScheduler::class)->update($matchGame, $request->validated());

        return back()->with('success', 'Match updated.');
    }

    public function updateMatchOfficials(Request $request, Event $event, Competition $competition, Fixture $fixture, MatchGame $matchGame): RedirectResponse
    {
        $this->ensureBelongsToEvent($event, $competition);
        $this->ensureFixtureBelongsToCompetition($competition, $fixture);
        $this->ensureMatchBelongsToFixture($fixture, $matchGame);
        $this->authorize('manageSchedule', $competition);

        $validated = $request->validate([
            'officials' => ['nullable', 'array'],
            'officials.*.official_id' => ['required', 'integer', Rule::exists('officials', 'id')],
            'officials.*.role' => ['nullable', Rule::in(\App\Enums\MatchOfficialRole::values())],
        ]);

        $officials = $validated['officials'] ?? [];

        // Validate officials belong to the event's organization and are approved
        foreach ($officials as $off) {
            $official = Official::query()
                ->where('organization_id', $event->organization_id)
                ->whereHas('registrations', fn ($q) => $q->where('event_id', $event->id)->where('status', 'approved'))
                ->find($off['official_id']);

            if (! $official) {
                return back()->withErrors(['officials' => 'One or more officials are not approved for this event.']);
            }
        }

        // Check for conflicts if scheduled
        if ($matchGame->scheduled_at) {
            $conflicts = app(\App\Support\ScheduleConflictDetector::class)->detect(
                $matchGame->scheduled_at,
                $matchGame->duration_minutes,
                $matchGame->venue_id,
                $matchGame->facility_id,
                $matchGame->participants->map(fn ($p) => [
                    'participant_type' => $p->participant_type,
                    'participant_id' => $p->participant_id,
                ])->toArray(),
                $officials,
                $matchGame->id
            );

            if (! empty($conflicts['officials'])) {
                return back()->withErrors(['officials' => $conflicts['officials']]);
            }
        }

        // Replace officials
        $matchGame->officials()->delete();

        foreach ($officials as $off) {
            MatchOfficial::create([
                'match_id' => $matchGame->id,
                'official_id' => $off['official_id'],
                'role' => $off['role'] ?? 'referee',
            ]);
        }

        return back()->with('success', 'Officials updated for match.');
    }

    public function destroyMatch(Event $event, Competition $competition, Fixture $fixture, MatchGame $matchGame): RedirectResponse
    {
        $this->ensureBelongsToEvent($event, $competition);
        $this->authorize('manageSchedule', $competition);
        $this->ensureFixtureBelongsToCompetition($competition, $fixture);
        $this->ensureMatchBelongsToFixture($fixture, $matchGame);

        $matchGame->delete();

        return back()->with('success', 'Match removed.');
    }

    private function ensureBelongsToEvent(Event $event, Competition $competition): void
    {
        abort_unless($competition->event_id === $event->id, 404);
    }

    private function ensureFixtureBelongsToCompetition(Competition $competition, Fixture $fixture): void
    {
        abort_unless($fixture->competition_id === $competition->id, 404);
    }

    private function ensureMatchBelongsToFixture(Fixture $fixture, MatchGame $match): void
    {
        abort_unless($match->fixture_id === $fixture->id, 404);
    }

    private function uniqueSlug(string $name, int $eventId, int $sportId): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 1;

        while (Competition::withTrashed()
            ->where('event_id', $eventId)
            ->where('sport_id', $sportId)
            ->where('slug', $slug)
            ->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    /**
     * @return array<string, mixed>
     */
    private function competitionPayload(Competition $competition): array
    {
        return [
            'id' => $competition->id,
            'name' => $competition->name,
            'slug' => $competition->slug,
            'status' => $competition->status->value,
            'notes' => $competition->notes,
            'sport' => $competition->sport?->only(['id', 'name', 'slug']),
            'format' => $competition->format?->only(['id', 'name', 'slug']),
            'supports_groups' => $competition->format?->supportsGroups() ?? false,
            'groups' => $competition->groups?->map(fn (CompetitionGroup $group) => [
                'id' => $group->id,
                'name' => $group->name,
                'slug' => $group->slug,
                'sort_order' => $group->sort_order,
            ]) ?? [],
            'rankings' => $competition->rankings?->map(fn ($ranking) => [
                'position' => $ranking->position,
                'name' => $ranking->rankable?->name,
                'points' => $ranking->points,
                'played' => $ranking->played,
                'won' => $ranking->won,
                'drawn' => $ranking->drawn,
                'lost' => $ranking->lost,
                'scored_for' => $ranking->scored_for,
                'scored_against' => $ranking->scored_against,
                'goal_difference' => $ranking->goalDifference(),
            ]) ?? [],
            'fixtures' => $competition->fixtures?->map(fn (Fixture $fixture) => [
                'id' => $fixture->id,
                'name' => $fixture->name,
                'round' => $fixture->round,
                'sort_order' => $fixture->sort_order,
                'group' => $fixture->group?->only(['id', 'name', 'slug']),
                'matches' => $fixture->matches->map(fn (MatchGame $match) => $this->matchPayload($match)),
            ]) ?? [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function matchPayload(MatchGame $match): array
    {
        return [
            'id' => $match->id,
            'scheduled_at' => $match->scheduled_at?->toDateTimeString(),
            'duration_minutes' => $match->duration_minutes,
            'status' => $match->status->value,
            'notes' => $match->notes,
            'venue' => $match->venue?->only(['id', 'name']),
            'facility' => $match->facility?->only(['id', 'name']),
            'participants' => $match->participants->map(fn ($participant) => [
                'side' => $participant->side->value,
                'participant_type' => $participant->participant_type,
                'participant_id' => $participant->participant_id,
                'name' => $participant->participant?->name,
            ]),
            'officials' => $match->officials->map(fn ($assignment) => [
                'official_id' => $assignment->official_id,
                'name' => $assignment->official?->name,
                'role' => $assignment->role->value,
            ]),
            'result' => $match->result ? [
                'id' => $match->result->id,
                'status' => $match->result->status->value,
                'home_score' => $match->result->data['home_score'] ?? null,
                'away_score' => $match->result->data['away_score'] ?? null,
                'winner_side' => $match->result->data['winner_side'] ?? null,
                'appeals' => $match->result->appeals->map(fn ($appeal) => [
                    'id' => $appeal->id,
                    'status' => $appeal->status->value,
                    'reason' => $appeal->reason,
                    'proposed_home_score' => $appeal->proposed_home_score,
                    'proposed_away_score' => $appeal->proposed_away_score,
                    'resolution_notes' => $appeal->resolution_notes,
                    'submitted_by' => $appeal->submittedBy?->only(['id', 'name']),
                    'reviewed_at' => $appeal->reviewed_at?->toDateTimeString(),
                ]),
            ] : null,
            'can_enter_result' => request()->user()?->can('enterResult', $match) ?? false,
            'can_submit_appeal' => $match->result
                ? (request()->user()?->can('create', [\App\Models\ResultAppeal::class, $match->result]) ?? false)
                : false,
            'can_resolve_appeal' => ($openAppeal = $match->result?->appeals
                ->first(fn ($appeal) => $appeal->status->isOpen())) !== null
                && (request()->user()?->can('resolve', $openAppeal) ?? false),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function bracketPayload(Competition $competition): array
    {
        if (! in_array($competition->format?->slug, ['knockout', 'group_stage', 'double_elimination'], true)) {
            return [];
        }

        return $competition->fixtures
            ->sortBy('sort_order')
            ->map(fn (Fixture $fixture) => [
                'round' => $fixture->round ?? $fixture->name,
                'matches' => $fixture->matches->map(fn (MatchGame $match) => [
                    'id' => $match->id,
                    'participants' => $match->participants->map(fn ($participant) => [
                        'side' => $participant->side->value,
                        'name' => $participant->participant?->name ?? 'TBD',
                    ]),
                    'result' => $match->result ? [
                        'home_score' => $match->result->data['home_score'] ?? null,
                        'away_score' => $match->result->data['away_score'] ?? null,
                        'status' => $match->result->status->value,
                    ] : null,
                ]),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function venueOptions(Event $event, int $sportId): array
    {
        return $event->venues()
            ->with('facilities:id,venue_id,name,slug')
            ->orderBy('venues.name')
            ->get(['venues.id', 'venues.name'])
            ->map(fn ($venue) => [
                'id' => $venue->id,
                'name' => $venue->name,
                'facilities' => $venue->facilities->map(fn ($facility) => $facility->only(['id', 'name', 'slug'])),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    private function teamOptions(Event $event, int $sportId): array
    {
        return Team::query()
            ->where('event_id', $event->id)
            ->where('sport_id', $sportId)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Team $team) => $team->only(['id', 'name']))
            ->values()
            ->all();
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    private function athleteOptions(Event $event): array
    {
        return Athlete::query()
            ->where('organization_id', $event->organization_id)
            ->whereHas('registrations', fn ($query) => $query
                ->where('event_id', $event->id)
                ->where('status', 'approved'))
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Athlete $athlete) => $athlete->only(['id', 'name']))
            ->values()
            ->all();
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    private function officialOptions(Event $event): array
    {
        return Official::query()
            ->where('organization_id', $event->organization_id)
            ->whereHas('registrations', fn ($query) => $query
                ->where('event_id', $event->id)
                ->where('status', 'approved'))
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Official $official) => $official->only(['id', 'name']))
            ->values()
            ->all();
    }

}