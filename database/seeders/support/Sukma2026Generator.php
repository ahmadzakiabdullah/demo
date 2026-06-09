<?php

namespace Database\Seeders\Support;

use App\Enums\CompetitionStatus;
use App\Enums\EventAssignmentRole;
use App\Enums\EventCadence;
use App\Enums\EventParticipantStatus;
use App\Enums\EventParticipantType;
use App\Enums\EventStatus;
use App\Enums\ParticipantUnitLabel;
use App\Enums\FacilityType;
use App\Enums\MatchOfficialRole;
use App\Enums\MatchParticipantSide;
use App\Enums\MatchStatus;
use App\Enums\MedalType;
use App\Enums\OfficialType;
use App\Enums\OrganizationStatus;
use App\Enums\OrganizationType;
use App\Enums\RegistrationStatus;
use App\Enums\ResultStatus;
use App\Enums\SportGender;
use App\Enums\SportStatus;
use App\Enums\TeamMemberRole;
use App\Models\Athlete;
use App\Models\Branch;
use App\Models\Competition;
use App\Models\CompetitionFormat;
use App\Models\CompetitionGroup;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\EventParticipant;
use App\Models\EventSeries;
use App\Models\EventType;
use App\Models\ParticipantSportEntry;
use App\Models\Facility;
use App\Models\Fixture;
use App\Models\MatchGame;
use App\Models\MatchOfficial;
use App\Models\MatchParticipant;
use App\Models\Medal;
use App\Models\MedalCeremony;
use App\Models\Official;
use App\Models\Organization;
use App\Models\Registration;
use App\Models\Result;
use App\Models\ResultAppeal;
use App\Models\Role;
use App\Models\Sport;
use App\Models\SportCategory;
use App\Models\SportDiscipline;
use App\Models\SportDivision;
use App\Models\Team;
use App\Models\User;
use App\Models\Venue;
use App\Services\SportStructureService;
use App\Support\DrawGenerator;
use App\Support\MedalAllocator;
use App\Support\RankingCalculator;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class Sukma2026Generator
{
    private string $dataPath;

    /** @var list<array<string, mixed>> */
    private array $contingentData = [];

    /** @var list<array<string, mixed>> */
    private array $sportData = [];

    /** @var list<array<string, mixed>> */
    private array $venueData = [];

    private ?Organization $msn = null;

    /** @var Collection<string, EventParticipant> */
    private Collection $contingents;

    private ?EventSeries $eventSeries = null;

    private ?Event $event = null;

    /** @var Collection<string, Sport> */
    private Collection $sports;

    /** @var Collection<int, Venue> */
    private Collection $venues;

    /** @var Collection<int, Athlete> */
    private Collection $athletes;

    /** @var Collection<int, Official> */
    private Collection $officials;

    /** @var Collection<int, User> */
    private Collection $coaches;

    /** @var array<string, int> */
    private array $stats = [];

    public function __construct(?string $dataPath = null)
    {
        $this->dataPath = $dataPath ?? database_path('seeders/data/sukma2026');
        $this->contingents = collect();
        $this->sports = collect();
        $this->venues = collect();
        $this->athletes = collect();
        $this->officials = collect();
        $this->coaches = collect();
    }

    /**
     * @return array<string, int|float|list<array<string, mixed>>>
     */
    public function run(bool $force = false): array
    {
        $this->loadReferenceData();

        $existing = Event::query()->where('slug', 'sukma-selangor-2026')->first();
        if ($existing !== null && ! $force) {
            $this->hydrateFromExisting($existing);
            $this->collectStats();
            $this->exportArtifacts();

            return $this->stats;
        }

        if ($existing !== null && $force) {
            $this->purgeExistingDemo($existing);
        }

        DB::transaction(function () {
            $this->seedOrganizations();
            $this->seedBranches();
            $this->seedEventSeries();
            $this->seedEvent();
            $this->seedEventParticipants();
            $this->seedVenues();
            $this->seedSports();
            $this->seedParticipantSportEntries();
            $this->seedPeople();
            $this->seedTeamsAndRegistrations();
            $this->seedCompetitionsAndDraws();
            $this->seedMatchScheduleAndResults();
            $this->seedRankingsMedalsAndCeremonies();
            $this->seedEventAssignments();
            $this->seedSampleAppeal();
            $this->collectStats();
        });

        $this->exportArtifacts();

        return $this->stats;
    }

    private function purgeExistingDemo(Event $event): void
    {
        $eventId = $event->id;
        $msnId = $event->organization_id;

        Medal::query()->where('event_id', $eventId)->delete();
        ResultAppeal::query()->where('organization_id', $msnId)->delete();
        Result::query()->whereHas('match.fixture.competition', fn ($q) => $q->where('event_id', $eventId))->delete();
        MatchOfficial::query()->whereHas('match.fixture.competition', fn ($q) => $q->where('event_id', $eventId))->delete();
        MatchParticipant::query()->whereHas('match.fixture.competition', fn ($q) => $q->where('event_id', $eventId))->delete();
        MatchGame::query()->whereHas('fixture.competition', fn ($q) => $q->where('event_id', $eventId))->delete();
        Fixture::query()->whereHas('competition', fn ($q) => $q->where('event_id', $eventId))->delete();
        DB::table('competition_participants')->whereIn('competition_id', Competition::query()->where('event_id', $eventId)->pluck('id'))->delete();
        Competition::query()->where('event_id', $eventId)->forceDelete();
        Registration::query()->where('event_id', $eventId)->forceDelete();
        DB::table('team_athlete')->whereIn('team_id', Team::query()->where('event_id', $eventId)->pluck('id'))->delete();
        Team::query()->where('event_id', $eventId)->forceDelete();
        Sport::query()->where('event_id', $eventId)->forceDelete();
        $event->venues()->detach();
        DB::table('event_sport_venue')->where('event_id', $eventId)->delete();
        MedalCeremony::query()->where('event_id', $eventId)->delete();
        $event->assignees()->detach();
        $event->forceDelete();

        ParticipantSportEntry::query()
            ->whereHas('eventParticipant', fn ($q) => $q->where('event_id', $eventId))
            ->forceDelete();
        EventParticipant::query()->where('event_id', $eventId)->forceDelete();
        Athlete::query()->where('organization_id', $msnId)->forceDelete();
        Official::query()->where('organization_id', $msnId)->forceDelete();
        User::query()->where('email', 'like', '%.sukma2026.%@sportos.demo')->delete();
        Venue::query()->where('organization_id', $msnId)->forceDelete();
        Branch::query()->where('organization_id', $msnId)->delete();
        EventSeries::query()->where('organization_id', $msnId)->where('slug', 'sukma')->forceDelete();
        Organization::query()->whereIn('slug', collect($this->contingentData)->pluck('slug'))->forceDelete();
        Organization::query()->where('slug', 'msn')->forceDelete();
    }

    private function hydrateFromExisting(Event $event): void
    {
        $this->event = $event;
        $this->msn = $event->organization;
        $this->eventSeries = $event->eventSeries;
        $this->contingents = EventParticipant::query()
            ->where('event_id', $event->id)
            ->get()
            ->keyBy(fn (EventParticipant $participant) => $participant->metadata['slug'] ?? $participant->code);
        $this->sports = Sport::query()->where('event_id', $event->id)->get()->keyBy('slug');
        $this->venues = Venue::query()->where('organization_id', $this->msn->id)->get();
        $this->athletes = Athlete::query()
            ->whereIn('event_participant_id', $this->contingents->pluck('id'))
            ->limit(500)
            ->get();
    }

    private function loadReferenceData(): void
    {
        $this->contingentData = json_decode(File::get("{$this->dataPath}/contingents.json"), true);
        $this->sportData = json_decode(File::get("{$this->dataPath}/sports.json"), true);
        $this->venueData = json_decode(File::get("{$this->dataPath}/venues.json"), true);
    }

    private function seedOrganizations(): void
    {
        $this->msn = Organization::query()->firstOrCreate(
            ['slug' => 'msn'],
            [
                'name' => 'Majlis Sukan Negara Malaysia',
                'type' => OrganizationType::Federation,
                'timezone' => 'Asia/Kuala_Lumpur',
                'locale' => 'ms',
                'status' => OrganizationStatus::Active,
            ],
        );

        $owner = User::query()->where('email', 'ahmadzaki@utem.edu.my')->first();
        $orgAdminRoleId = Role::query()->where('slug', Role::ORG_ADMIN)->whereNull('organization_id')->value('id');

        if ($owner && $orgAdminRoleId) {
            $owner->organizations()->syncWithoutDetaching([
                $this->msn->id => ['role_id' => $orgAdminRoleId, 'status' => 'active'],
            ]);
        }
    }

    private function seedBranches(): void
    {
        $branches = [
            ['name' => 'Jawatankuasa Pengurusan Tertinggi', 'code' => 'JPT'],
            ['name' => 'Pengurusan Teknikal Sukan', 'code' => 'PTS'],
            ['name' => 'Perubatan & Anti-Doping', 'code' => 'MED'],
            ['name' => 'Media & Komunikasi', 'code' => 'MEDCOM'],
            ['name' => 'Logistik & Penginapan', 'code' => 'LOG'],
            ['name' => 'Hos Negeri Selangor', 'code' => 'HOST-SGR'],
        ];

        foreach ($branches as $branch) {
            Branch::query()->firstOrCreate(
                [
                    'organization_id' => $this->msn->id,
                    'code' => $branch['code'],
                ],
                ['name' => $branch['name']],
            );
        }
    }

    private function seedEventSeries(): void
    {
        $this->eventSeries = EventSeries::query()->firstOrCreate(
            [
                'organization_id' => $this->msn->id,
                'slug' => 'sukma',
            ],
            [
                'name' => 'Sukan Malaysia (SUKMA)',
                'cadence' => EventCadence::Biennial,
                'description' => 'Sukan Malaysia — dwi tahunan',
            ],
        );
    }

    private function seedEvent(): void
    {
        $eventTypeId = EventType::query()->where('slug', 'multi-sport')->value('id');
        $eventCategoryId = EventCategory::query()->where('slug', 'elite')->value('id');

        $this->event = Event::query()->firstOrCreate(
            [
                'organization_id' => $this->msn->id,
                'slug' => 'sukma-selangor-2026',
            ],
            [
                'event_type_id' => $eventTypeId,
                'event_category_id' => $eventCategoryId,
                'event_series_id' => $this->eventSeries?->id,
                'name' => 'SUKMA Selangor 2026',
                'edition_year' => 2026,
                'cadence' => EventCadence::Biennial,
                'participant_unit_label' => ParticipantUnitLabel::State,
                'status' => EventStatus::Active,
                'location' => 'Selangor, Malaysia',
                'description' => 'Sukan Malaysia ke-22 di negeri Selangor. 16 kontinjen, 35+ sukan, 8,000+ atlet.',
                'starts_at' => Carbon::parse('2026-08-15 08:00:00', 'Asia/Kuala_Lumpur'),
                'ends_at' => Carbon::parse('2026-08-24 22:00:00', 'Asia/Kuala_Lumpur'),
            ],
        );

        $this->event->update([
            'event_series_id' => $this->eventSeries?->id,
            'edition_year' => 2026,
            'cadence' => EventCadence::Biennial,
            'participant_unit_label' => ParticipantUnitLabel::State,
        ]);
    }

    private function seedEventParticipants(): void
    {
        foreach ($this->contingentData as $row) {
            $participant = EventParticipant::query()->firstOrCreate(
                [
                    'event_id' => $this->event->id,
                    'code' => $row['code'],
                ],
                [
                    'organization_id' => $this->msn->id,
                    'type' => EventParticipantType::State,
                    'name' => $row['name'],
                    'status' => EventParticipantStatus::Active,
                    'metadata' => [
                        'slug' => $row['slug'],
                        'logo_path' => "branding/sukma2026/{$row['slug']}.svg",
                    ],
                ],
            );

            $this->contingents->put($row['slug'], $participant);
        }
    }

    private function seedParticipantSportEntries(): void
    {
        $teamSports = ['bola-sepak', 'hoki', 'bola-keranjang', 'bola-tampar', 'bola-jaring', 'ragbi', 'kriket', 'kabaddi'];
        $now = now();

        foreach ($this->contingents as $participant) {
            foreach ($teamSports as $sportSlug) {
                $sport = $this->sports->get($sportSlug);
                if ($sport === null) {
                    continue;
                }

                ParticipantSportEntry::query()->firstOrCreate(
                    [
                        'event_participant_id' => $participant->id,
                        'sport_id' => $sport->id,
                        'sport_category_id' => null,
                        'sport_division_id' => null,
                    ],
                    [
                        'status' => RegistrationStatus::Approved,
                        'submitted_at' => $now,
                        'approved_at' => $now,
                    ],
                );
            }
        }
    }

    private function seedVenues(): void
    {
        foreach ($this->venueData as $index => $row) {
            $venue = Venue::query()->firstOrCreate(
                [
                    'organization_id' => $this->msn->id,
                    'slug' => $row['slug'],
                ],
                [
                    'name' => $row['name'],
                    'address' => $row['address'],
                    'capacity' => $row['capacity'],
                    'timezone' => 'Asia/Kuala_Lumpur',
                    'notes' => "Bandar: {$row['city']} | Negeri: {$row['state']}",
                ],
            );

            Facility::query()->firstOrCreate(
                [
                    'venue_id' => $venue->id,
                    'slug' => Str::slug($row['facility_name']),
                ],
                [
                    'name' => $row['facility_name'],
                    'type' => FacilityType::from($row['facility_type']),
                    'capacity' => (int) round($row['capacity'] * 0.6),
                    'sort_order' => 1,
                ],
            );

            $this->venues->push($venue);

            $this->event->venues()->syncWithoutDetaching([
                $venue->id => [
                    'is_primary' => $index === 0,
                    'notes' => 'Venue SUKMA 2026',
                ],
            ]);
        }
    }

    private function seedSports(): void
    {
        $structureService = app(SportStructureService::class);

        foreach ($this->sportData as $row) {
            $sport = Sport::query()->firstOrCreate(
                [
                    'event_id' => $this->event->id,
                    'slug' => $row['slug'],
                ],
                [
                    'name' => $row['name'],
                    'template_slug' => $row['template_slug'] ?? null,
                    'status' => SportStatus::Active,
                    'rules' => [
                        'category' => $row['category'],
                        'sukma_code' => strtoupper(substr($row['slug'], 0, 3)),
                    ],
                ],
            );

            if (! empty($row['template_slug']) && $sport->disciplines()->count() === 0) {
                $structureService->applyTemplate($sport, $row['template_slug']);
            }

            if (! empty($row['events'])) {
                $this->seedSportEventsFromCatalog($sport, $row['events'], 'Acara Rasmi');
                $this->seedSportEventsFromCatalog(
                    $sport,
                    ['Peringkat Kelayakan', 'Suku Akhir', 'Separuh Akhir', 'Akhir'],
                    'Pusingan Pertandingan',
                );
            } elseif ($sport->disciplines()->count() === 0) {
                $this->seedSportEventsFromCatalog($sport, ['Open'], 'Acara Umum');
            }

            $venue = $this->findVenueForSport($row['slug']);
            if ($venue) {
                $this->event->venues()->syncWithoutDetaching([$venue->id => ['is_primary' => false, 'notes' => null]]);
                DB::table('event_sport_venue')->updateOrInsert(
                    [
                        'event_id' => $this->event->id,
                        'sport_id' => $sport->id,
                        'venue_id' => $venue->id,
                    ],
                    ['created_at' => now(), 'updated_at' => now()],
                );
            }

            $this->sports->put($row['slug'], $sport);
        }
    }

    /**
     * @param  list<string>  $events
     */
    private function seedSportEventsFromCatalog(Sport $sport, array $events, string $disciplineName = 'Acara Utama'): void
    {
        $disciplineSlug = Str::slug($disciplineName);
        $discipline = SportDiscipline::query()->firstOrCreate(
            ['sport_id' => $sport->id, 'slug' => $disciplineSlug],
            ['name' => $disciplineName, 'sort_order' => $sport->disciplines()->count()],
        );

        foreach ($events as $index => $eventName) {
            $slug = Str::slug($eventName);
            $gender = $this->inferGender($eventName);

            $category = SportCategory::query()->firstOrCreate(
                ['sport_discipline_id' => $discipline->id, 'slug' => $slug],
                [
                    'name' => $eventName,
                    'gender' => $gender,
                    'min_age' => 16,
                    'max_age' => 25,
                    'sort_order' => $index,
                ],
            );

            SportDivision::query()->firstOrCreate(
                ['sport_category_id' => $category->id, 'slug' => 'open'],
                ['name' => 'Open', 'sort_order' => 0],
            );
        }
    }

    private function inferGender(string $eventName): SportGender
    {
        if (str_contains($eventName, 'Wanita')) {
            return SportGender::Female;
        }

        if (str_contains($eventName, 'Lelaki')) {
            return SportGender::Male;
        }

        if (str_contains($eventName, 'Campuran')) {
            return SportGender::Mixed;
        }

        return SportGender::Open;
    }

    private function findVenueForSport(string $sportSlug): ?Venue
    {
        foreach ($this->venueData as $row) {
            if (in_array($sportSlug, $row['sports'], true)) {
                return $this->venues->firstWhere('slug', $row['slug']);
            }
        }

        return $this->venues->first();
    }

    private function seedPeople(): void
    {
        $this->seedCoaches();
        $this->seedAthletes();
        $this->seedOfficials();
        $this->seedVolunteers();
    }

    private function seedCoaches(): void
    {
        $names = $this->malaysianNames();
        $sportSlugs = $this->sports->keys()->all();

        for ($i = 0; $i < 100; $i++) {
            $name = $names[$i % count($names)];
            $user = User::query()->firstOrCreate(
                ['email' => sprintf('coach.sukma2026.%03d@sportos.demo', $i + 1)],
                [
                    'name' => "Jurulatih {$name}",
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ],
            );

            $this->coaches->push($user);
        }
    }

    private function seedAthletes(): void
    {
        $names = $this->malaysianNames();
        $contingentList = $this->contingents->values();
        $sportSlugs = $this->sports->keys()->all();
        $now = now();

        for ($i = 0; $i < 500; $i++) {
            $contingent = $contingentList[$i % $contingentList->count()];
            $name = $names[($i * 7) % count($names)];
            $gender = $i % 2 === 0 ? SportGender::Male : SportGender::Female;
            $dob = Carbon::parse('2026-08-15')->subYears(rand(16, 25))->subDays(rand(0, 364));

            $athlete = Athlete::query()->create([
                'organization_id' => $this->msn->id,
                'event_participant_id' => $contingent->id,
                'name' => $name,
                'dob' => $dob,
                'gender' => $gender,
                'nationality' => 'MYS',
                'id_number' => sprintf('%s-%04d', $contingent->code ?? $contingent->id, $i + 1),
                'medical_clearance' => true,
            ]);

            $sport = $this->sports->get($sportSlugs[$i % count($sportSlugs)]);
            $category = $sport?->disciplines()->first()?->categories()->first();

            if ($sport && $category) {
                Registration::query()->create([
                    'event_id' => $this->event->id,
                    'sport_id' => $sport->id,
                    'registrable_type' => Athlete::class,
                    'registrable_id' => $athlete->id,
                    'sport_category_id' => $category->id,
                    'status' => RegistrationStatus::Approved,
                    'submitted_at' => $now,
                    'verified_at' => $now,
                    'approved_at' => $now,
                ]);
            }

            $this->athletes->push($athlete);
        }
    }

    private function seedOfficials(): void
    {
        $types = OfficialType::cases();
        $names = $this->malaysianNames();

        for ($i = 0; $i < 150; $i++) {
            $this->officials->push(Official::query()->create([
                'organization_id' => $this->msn->id,
                'name' => $names[($i * 3) % count($names)],
                'email' => sprintf('official.sukma2026.%03d@sportos.demo', $i + 1),
                'type' => $types[$i % count($types)],
                'certification_level' => ['National A', 'National B', 'International'][rand(0, 2)],
                'certification_expires_at' => now()->addYears(2),
            ]));
        }
    }

    private function seedVolunteers(): void
    {
        $volunteerRoleId = Role::query()->where('slug', Role::VOLUNTEER)->whereNull('organization_id')->value('id');
        $names = $this->malaysianNames();
        $universities = ['UiTM Shah Alam', 'UM', 'UKM', 'UPM', 'UTM', 'USM', 'MMU', 'Sunway University', 'Taylor\'s University'];

        for ($i = 0; $i < 100; $i++) {
            $user = User::query()->firstOrCreate(
                ['email' => sprintf('volunteer.sukma2026.%03d@sportos.demo', $i + 1)],
                [
                    'name' => $names[($i * 5) % count($names)],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ],
            );

            if ($volunteerRoleId) {
                $user->organizations()->syncWithoutDetaching([
                    $this->msn->id => ['role_id' => $volunteerRoleId, 'status' => 'active'],
                ]);
            }
        }
    }

    private function seedTeamsAndRegistrations(): void
    {
        $teamSports = ['bola-sepak', 'hoki', 'bola-keranjang', 'bola-tampar', 'bola-jaring', 'ragbi', 'kriket', 'kabaddi'];
        $now = now();

        foreach ($teamSports as $sportSlug) {
            $sport = $this->sports->get($sportSlug);
            if ($sport === null) {
                continue;
            }

            $coachIndex = 0;
            foreach ($this->contingents as $slug => $contingent) {
                $contingentMeta = collect($this->contingentData)->firstWhere('slug', $slug);
                $abbrev = $contingentMeta['abbrev'] ?? strtoupper(substr($slug, 0, 3));

                $team = Team::query()->create([
                    'organization_id' => $this->msn->id,
                    'event_participant_id' => $contingent->id,
                    'event_id' => $this->event->id,
                    'sport_id' => $sport->id,
                    'name' => "{$abbrev} {$sport->name}",
                    'slug' => Str::slug("{$abbrev}-{$sportSlug}"),
                    'coach_user_id' => $this->coaches[$coachIndex % $this->coaches->count()]->id,
                    'manager_user_id' => $this->coaches[($coachIndex + 1) % $this->coaches->count()]->id,
                    'notes' => "Kontinjen {$contingent->name}",
                ]);

                $coachIndex += 2;

                $roster = $this->athletes
                    ->where('event_participant_id', $contingent->id)
                    ->take(18)
                    ->values();

                foreach ($roster as $index => $athlete) {
                    $team->athletes()->attach($athlete->id, [
                        'role' => $index === 0 ? TeamMemberRole::Captain->value : TeamMemberRole::Member->value,
                        'jersey_number' => (string) ($index + 1),
                    ]);
                }

                Registration::query()->create([
                    'event_id' => $this->event->id,
                    'sport_id' => $sport->id,
                    'registrable_type' => Team::class,
                    'registrable_id' => $team->id,
                    'status' => RegistrationStatus::Approved,
                    'submitted_at' => $now,
                    'verified_at' => $now,
                    'approved_at' => $now,
                ]);
            }
        }
    }

    private function seedCompetitionsAndDraws(): void
    {
        $drawGenerator = app(DrawGenerator::class);

        $plans = [
            ['sport' => 'bola-sepak', 'slug' => 'bola-sepak-lelaki', 'name' => 'Bola Sepak Lelaki', 'format' => 'round_robin'],
            ['sport' => 'hoki', 'slug' => 'hoki-lelaki', 'name' => 'Hoki Lelaki', 'format' => 'round_robin'],
            ['sport' => 'bola-keranjang', 'slug' => 'bola-keranjang-lelaki', 'name' => 'Bola Keranjang Lelaki', 'format' => 'group_stage', 'groups' => 4],
            ['sport' => 'badminton', 'slug' => 'badminton-lelaki', 'name' => 'Badminton Perseorangan Lelaki', 'format' => 'knockout'],
            ['sport' => 'bola-tampar', 'slug' => 'bola-tampar-wanita', 'name' => 'Bola Tampar Wanita', 'format' => 'knockout'],
        ];

        foreach ($plans as $plan) {
            $sport = $this->sports->get($plan['sport']);
            if ($sport === null) {
                continue;
            }

            $format = CompetitionFormat::query()->where('slug', $plan['format'])->firstOrFail();

            $competition = Competition::query()->firstOrCreate(
                [
                    'event_id' => $this->event->id,
                    'sport_id' => $sport->id,
                    'slug' => $plan['slug'],
                ],
                [
                    'organization_id' => $this->msn->id,
                    'competition_format_id' => $format->id,
                    'name' => $plan['name'],
                    'status' => CompetitionStatus::Active,
                    'settings' => $plan['format'] === 'group_stage'
                        ? ['group_count' => $plan['groups'] ?? 4, 'group_advance_count' => 2]
                        : ['seeding' => 'name'],
                ],
            );

            if ($plan['format'] === 'knockout' && $plan['sport'] === 'badminton') {
                $this->seedIndividualEntryTeams($sport, 'men-singles');
            }

            if ($plan['format'] === 'group_stage') {
                $this->seedGroupStageGroups($competition, $plan['groups'] ?? 4);
            }

            $drawGenerator->generate($competition);
        }
    }

    private function seedIndividualEntryTeams(Sport $sport, string $categorySlug): void
    {
        $now = now();
        $index = 0;

        foreach ($this->contingents as $slug => $contingent) {
            $athlete = $this->athletes->where('event_participant_id', $contingent->id)->first();
            if ($athlete === null) {
                continue;
            }

            $team = Team::query()->create([
                'organization_id' => $this->msn->id,
                'event_participant_id' => $contingent->id,
                'event_id' => $this->event->id,
                'sport_id' => $sport->id,
                'name' => $athlete->name,
                'slug' => Str::slug("{$slug}-{$categorySlug}-{$index}"),
                'coach_user_id' => $this->coaches[$index % $this->coaches->count()]->id,
            ]);

            $team->athletes()->attach($athlete->id, ['role' => TeamMemberRole::Member->value, 'jersey_number' => '1']);

            Registration::query()->create([
                'event_id' => $this->event->id,
                'sport_id' => $sport->id,
                'registrable_type' => Team::class,
                'registrable_id' => $team->id,
                'status' => RegistrationStatus::Approved,
                'submitted_at' => $now,
                'verified_at' => $now,
                'approved_at' => $now,
            ]);

            $index++;
        }
    }

    private function seedGroupStageGroups(Competition $competition, int $groupCount): void
    {
        $teams = Team::query()
            ->where('event_id', $competition->event_id)
            ->where('sport_id', $competition->sport_id)
            ->orderBy('name')
            ->get();

        $letters = range('A', 'Z');
        foreach ($teams->chunk(ceil($teams->count() / $groupCount)) as $groupIndex => $chunk) {
            $group = CompetitionGroup::query()->firstOrCreate(
                [
                    'competition_id' => $competition->id,
                    'slug' => 'kumpulan-'.strtolower($letters[$groupIndex]),
                ],
                [
                    'name' => 'Kumpulan '.$letters[$groupIndex],
                    'sort_order' => $groupIndex,
                ],
            );

            foreach ($chunk as $team) {
                DB::table('competition_participants')->updateOrInsert(
                    [
                        'competition_id' => $competition->id,
                        'participant_type' => Team::class,
                        'participant_id' => $team->id,
                    ],
                    [
                        'seed' => $team->id % 16,
                        'ladder_rank' => 0,
                        'swiss_points' => 0,
                        'swiss_buchholz' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                );
            }
        }
    }

    private function seedMatchScheduleAndResults(): void
    {
        $matches = MatchGame::query()
            ->whereHas('fixture.competition', fn ($q) => $q->where('event_id', $this->event->id))
            ->with(['participants', 'fixture.competition'])
            ->orderBy('id')
            ->get();

        $start = Carbon::parse('2026-08-16 09:00:00', 'Asia/Kuala_Lumpur');
        $venueCycle = $this->venues->values();
        $officialCycle = $this->officials->values();
        $strongStates = ['sgr', 'joh', 'swk', 'prk', 'kul'];

        foreach ($matches as $index => $match) {
            $venue = $venueCycle[$index % $venueCycle->count()];
            $facility = $venue->facilities()->first();
            $scheduledAt = $start->copy()->addHours(intdiv($index, 4) * 3 + ($index % 4));

            $status = match (true) {
                $index % 10 === 0 => MatchStatus::InProgress,
                $index % 10 < 7 => MatchStatus::Completed,
                default => MatchStatus::Scheduled,
            };

            $match->update([
                'venue_id' => $venue->id,
                'facility_id' => $facility?->id,
                'scheduled_at' => $scheduledAt,
                'duration_minutes' => 90,
                'status' => $status,
            ]);

            if ($officialCycle->isNotEmpty()) {
                MatchOfficial::query()->firstOrCreate(
                    [
                        'match_id' => $match->id,
                        'official_id' => $officialCycle[$index % $officialCycle->count()]->id,
                    ],
                    ['role' => MatchOfficialRole::Referee->value],
                );
            }

            if ($status !== MatchStatus::Completed) {
                continue;
            }

            $home = $match->participants->firstWhere('side', MatchParticipantSide::Home);
            $away = $match->participants->firstWhere('side', MatchParticipantSide::Away);

            if ($home === null || $away === null) {
                continue;
            }

            $homeStrength = $this->participantStrength($home, $strongStates);
            $awayStrength = $this->participantStrength($away, $strongStates);
            $homeScore = $this->simulateScore($homeStrength, $awayStrength);
            $awayScore = $this->simulateScore($awayStrength, $homeStrength);

            Result::query()->updateOrCreate(
                ['match_id' => $match->id],
                [
                    'entered_by' => $this->coaches->first()?->id,
                    'data' => [
                        'home_score' => $homeScore,
                        'away_score' => $awayScore,
                        'winner_side' => $homeScore >= $awayScore ? 'home' : 'away',
                    ],
                    'status' => ResultStatus::Confirmed,
                    'confirmed_by' => $this->coaches->first()?->id,
                    'confirmed_at' => $scheduledAt->copy()->addHours(2),
                ],
            );
        }
    }

    private function participantStrength(MatchParticipant $participant, array $strongStates): int
    {
        $model = $participant->participant_type::query()->find($participant->participant_id);

        if ($model instanceof Team) {
            foreach ($strongStates as $abbrev) {
                if (str_starts_with(strtolower($model->slug), strtolower($abbrev))) {
                    return 3;
                }
            }

            return 1;
        }

        return 2;
    }

    private function simulateScore(int $strength, int $opponentStrength): int
    {
        $base = rand(0, 3) + $strength;
        if ($strength > $opponentStrength) {
            $base += rand(0, 2);
        }

        return max(0, min(5, $base));
    }

    private function seedRankingsMedalsAndCeremonies(): void
    {
        $rankingCalculator = app(RankingCalculator::class);
        $medalAllocator = app(MedalAllocator::class);

        $competitions = Competition::query()->where('event_id', $this->event->id)->get();

        foreach ($competitions as $competition) {
            $rankingCalculator->recalculate($competition);
            $medalAllocator->allocate($competition);
        }

        $ceremonySports = ['bola-sepak', 'badminton', 'olahraga', 'akuatik', 'hoki'];
        foreach ($ceremonySports as $index => $sportSlug) {
            $sport = $this->sports->get($sportSlug);
            $venue = $this->venues[$index % $this->venues->count()] ?? $this->venues->first();

            MedalCeremony::query()->firstOrCreate(
                [
                    'event_id' => $this->event->id,
                    'name' => 'Majlis Penyampaian Pingat — '.($sport?->name ?? 'Umum'),
                ],
                [
                    'organization_id' => $this->msn->id,
                    'sport_id' => $sport?->id,
                    'venue_id' => $venue?->id,
                    'scheduled_at' => Carbon::parse('2026-08-'.(18 + $index).' 19:00:00', 'Asia/Kuala_Lumpur'),
                    'duration_minutes' => 90,
                    'notes' => 'Majlis pingat SUKMA 2026',
                ],
            );
        }
    }

    private function seedEventAssignments(): void
    {
        $organizers = User::query()
            ->where('email', 'like', 'coach.sukma2026.%')
            ->limit(5)
            ->get();

        $roles = [
            EventAssignmentRole::EventOrganizer,
            EventAssignmentRole::SportsManager,
            EventAssignmentRole::TeamManager,
        ];

        foreach ($organizers as $index => $user) {
            $this->event->assignees()->syncWithoutDetaching([
                $user->id => ['role' => $roles[$index % count($roles)]->value],
            ]);
        }
    }

    private function seedSampleAppeal(): void
    {
        $result = Result::query()
            ->whereHas('match.fixture.competition', fn ($q) => $q->where('event_id', $this->event->id))
            ->where('status', ResultStatus::Confirmed)
            ->first();

        $submitter = $this->coaches->first();
        if ($result === null || $submitter === null) {
            return;
        }

        ResultAppeal::query()->firstOrCreate(
            ['result_id' => $result->id, 'submitted_by' => $submitter->id],
            [
                'organization_id' => $this->msn->id,
                'reason' => 'Bantahan keputusan perlawanan kuarter akhir — jaringan tidak sah dikira.',
                'status' => \App\Enums\AppealStatus::UnderReview,
                'proposed_home_score' => ((int) ($result->data['home_score'] ?? 0)) + 1,
                'proposed_away_score' => (int) ($result->data['away_score'] ?? 0),
            ],
        );
    }

    private function collectStats(): void
    {
        $eventCount = SportCategory::query()
            ->whereHas('discipline.sport', fn ($q) => $q->where('event_id', $this->event->id))
            ->count();

        $medalTally = Medal::query()
            ->where('event_id', $this->event->id)
            ->get()
            ->groupBy(fn (Medal $medal) => $medal->medalable_type::query()->find($medal->medalable_id)?->event_participant_id)
            ->map(function ($medals) {
                return [
                    'gold' => $medals->where('type', MedalType::Gold)->count(),
                    'silver' => $medals->where('type', MedalType::Silver)->count(),
                    'bronze' => $medals->where('type', MedalType::Bronze)->count(),
                ];
            });

        $this->stats = [
            'event' => $this->event->name,
            'organizations' => Organization::query()->count(),
            'contingents' => $this->contingents->count(),
            'branches' => Branch::query()->where('organization_id', $this->msn->id)->count(),
            'venues' => Venue::query()->where('organization_id', $this->msn->id)->count(),
            'facilities' => Facility::query()->whereIn('venue_id', $this->venues->pluck('id'))->count(),
            'sports' => Sport::query()->where('event_id', $this->event->id)->count(),
            'sport_events' => $eventCount,
            'athletes' => Athlete::query()->count(),
            'teams' => Team::query()->where('event_id', $this->event->id)->count(),
            'officials' => Official::query()->where('organization_id', $this->msn->id)->count(),
            'coaches' => User::query()->where('email', 'like', 'coach.sukma2026.%')->count(),
            'volunteers' => User::query()->where('email', 'like', 'volunteer.sukma2026.%')->count(),
            'competitions' => Competition::query()->where('event_id', $this->event->id)->count(),
            'fixtures' => Fixture::query()->whereHas('competition', fn ($q) => $q->where('event_id', $this->event->id))->count(),
            'matches' => MatchGame::query()->whereHas('fixture.competition', fn ($q) => $q->where('event_id', $this->event->id))->count(),
            'results' => Result::query()->whereHas('match.fixture.competition', fn ($q) => $q->where('event_id', $this->event->id))->count(),
            'medals' => Medal::query()->where('event_id', $this->event->id)->count(),
            'medal_ceremonies' => MedalCeremony::query()->where('event_id', $this->event->id)->count(),
            'registrations' => Registration::query()->where('event_id', $this->event->id)->count(),
            'appeals' => ResultAppeal::query()->where('organization_id', $this->msn->id)->count(),
            'medal_tally_by_contingent' => $this->formatMedalTally($medalTally),
        ];
    }

    /**
     * @param  Collection<int|string, array{gold: int, silver: int, bronze: int}>  $medalTally
     * @return list<array{contingent: string, gold: int, silver: int, bronze: int, total: int}>
     */
    private function formatMedalTally(Collection $medalTally): array
    {
        $rows = [];

        foreach ($medalTally as $participantId => $counts) {
            $participant = EventParticipant::query()->find($participantId);
            if ($participant === null) {
                continue;
            }

            $rows[] = [
                'contingent' => $participant->name,
                'gold' => $counts['gold'],
                'silver' => $counts['silver'],
                'bronze' => $counts['bronze'],
                'total' => $counts['gold'] + $counts['silver'] + $counts['bronze'],
            ];
        }

        usort($rows, fn ($a, $b) => $b['gold'] <=> $a['gold'] ?: $b['silver'] <=> $a['silver']);

        return $rows;
    }

    private function exportArtifacts(): void
    {
        $samplesPath = "{$this->dataPath}/samples";
        File::ensureDirectoryExists($samplesPath);

        File::put("{$this->dataPath}/summary.json", json_encode($this->stats, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->exportAthletesCsv("{$samplesPath}/athletes.csv");
        $this->exportMedalTallyCsv("{$samplesPath}/medal_tally.csv");
        $this->exportSqlSample("{$samplesPath}/insert_sample.sql");
    }

    private function exportAthletesCsv(string $path): void
    {
        $handle = fopen($path, 'w');
        fputcsv($handle, ['nombor_atlet', 'nama_penuh', 'ic_passport', 'jantina', 'tarikh_lahir', 'umur', 'negeri', 'sukan', 'status']);

        $this->athletes->take(50)->each(function (Athlete $athlete) use ($handle) {
            $registration = $athlete->registrations()->where('event_id', $this->event->id)->first();
            $sport = $registration ? Sport::query()->find($registration->sport_id) : null;

            fputcsv($handle, [
                $athlete->id_number,
                $athlete->name,
                $athlete->id_number,
                $athlete->gender?->value,
                $athlete->dob?->toDateString(),
                $athlete->ageAt($this->event->starts_at),
                $athlete->organization?->name,
                $sport?->name,
                'approved',
            ]);
        });

        fclose($handle);
    }

    private function exportMedalTallyCsv(string $path): void
    {
        $handle = fopen($path, 'w');
        fputcsv($handle, ['negeri', 'emas', 'perak', 'gangsa', 'jumlah']);

        foreach ($this->stats['medal_tally_by_contingent'] as $row) {
            fputcsv($handle, [$row['contingent'], $row['gold'], $row['silver'], $row['bronze'], $row['total']]);
        }

        fclose($handle);
    }

    private function exportSqlSample(string $path): void
    {
        $sql = <<<SQL
-- SUKMA Selangor 2026 — sample INSERT statements (SportOS schema)
-- Run full dataset via: php artisan db:seed --class=Sukma2026Seeder

INSERT INTO organizations (name, slug, type, timezone, locale, status, created_at, updated_at)
VALUES ('Majlis Sukan Negara Malaysia', 'msn', 'federation', 'Asia/Kuala_Lumpur', 'ms', 'active', NOW(), NOW());

INSERT INTO organizations (name, slug, type, timezone, locale, status, created_at, updated_at)
VALUES ('Kontinjen Selangor', 'selangor', 'federation', 'Asia/Kuala_Lumpur', 'ms', 'active', NOW(), NOW());

INSERT INTO events (organization_id, event_type_id, event_category_id, name, slug, status, location, starts_at, ends_at, created_at, updated_at)
VALUES (1, 1, 3, 'SUKMA Selangor 2026', 'sukma-selangor-2026', 'active', 'Selangor, Malaysia', '2026-08-15 08:00:00', '2026-08-24 22:00:00', NOW(), NOW());

INSERT INTO venues (organization_id, name, slug, address, capacity, timezone, created_at, updated_at)
VALUES (1, 'Stadium Nasional Bukit Jalil', 'stadium-nasional-bukit-jalil', 'Jalan Stadium Nasional, Bukit Jalil', 87411, 'Asia/Kuala_Lumpur', NOW(), NOW());
SQL;

        File::put($path, $sql);
    }

    /**
     * @return list<string>
     */
    private function malaysianNames(): array
    {
        return [
            'Ahmad Rizal bin Abdullah', 'Siti Nurhaliza binti Hassan', 'Tan Wei Ming', 'Priya a/p Subramaniam',
            'Muhammad Hafiz bin Omar', 'Nurul Ain binti Kamal', 'Lim Chee Keong', 'Kavitha a/p Rajan',
            'Amirul Hakimi bin Rosli', 'Farah Diyana binti Ahmad', 'Ong Boon Huat', 'Deepa a/p Muthu',
            'Syafiq Iqbal bin Zulkifli', 'Aisyah Humaira binti Ismail', 'Chong Wai Lun', 'Mei Ling binti Wong',
            'Haziq Danish bin Saiful', 'Nur Atiqah binti Rahman', 'Rajesh a/l Krishnan', 'Wong Li Ling',
            'Firdaus bin Mohd Ali', 'Zarith Sofiya binti Yusof', 'Lee Jun Wei', 'Shalini a/p Gunasegaran',
            'Irfan Haikal bin Mahmud', 'Balqis binti Azman', 'Ng Zhi Yang', 'Saraswathy a/p Ramasamy',
            'Luqman Hakim bin Salleh', 'Damia Sofea binti Khairul', 'Goh Tian Fu', 'Anisah binti Harun',
            'Roland Dominic anak Majang', 'Melissa anak Joseph', 'Steven anak Jimbai', 'Nurul Huda binti Sabri',
            'Arif bin Zakaria', 'Hana binti Fadzil', 'Vikram a/l Suresh', 'Yasmin binti Abdullah',
            'Khairul Anuar bin Musa', 'Puteri Aina binti Roslan', 'Jason Tan Wei Jie', 'Revathy a/p Chandran',
            'Azman bin Hamzah', 'Nadia binti Ghazali', 'Chen Jia Hui', 'Mohd Faizal bin Ramli',
            'Intan Syafiqah binti Omar', 'Harith bin Jamaluddin', 'Saravanan a/l Muniandy', 'Felicia anak Buda',
        ];
    }
}