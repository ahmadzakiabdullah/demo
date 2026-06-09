<?php

use App\Http\Controllers\Admin\AthleteController;
use App\Http\Controllers\Admin\CompetitionController;
use App\Http\Controllers\Admin\OfficialController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\EventParticipantController;
use App\Http\Controllers\Admin\ParticipantSportEntryController;
use App\Http\Controllers\Admin\EventVenueController;
use App\Http\Controllers\Admin\FacilityController;
use App\Http\Controllers\Admin\MedalCeremonyController;
use App\Http\Controllers\Admin\MedalController;
use App\Http\Controllers\Admin\RankingController;
use App\Http\Controllers\Admin\RegistrationController;
use App\Http\Controllers\Admin\ResultAppealController;
use App\Http\Controllers\Admin\ResultController;
use App\Http\Controllers\Admin\ScheduleController;
use App\Http\Controllers\Admin\OrganizationController;
use App\Http\Controllers\Admin\OrganizationSwitchController;
use App\Http\Controllers\Admin\SportController;
use App\Http\Controllers\Admin\TeamController;
use App\Http\Controllers\Admin\AccreditationController;
use App\Http\Controllers\Admin\AccreditationBadgeController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VenueController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::middleware('verified')->prefix('admin')->name('admin.')->group(function () {
        Route::post('organization/switch', [OrganizationSwitchController::class, 'store'])
            ->name('organization.switch');
        Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
        Route::resource('events', EventController::class);
        Route::get('events/{event}/participants/import', [EventParticipantController::class, 'importForm'])
            ->name('events.participants.import');
        Route::post('events/{event}/participants/import', [EventParticipantController::class, 'import'])
            ->name('events.participants.import.store');
        Route::get('events/{event}/participants/import/template', [EventParticipantController::class, 'importTemplate'])
            ->name('events.participants.import.template');
        Route::resource('events.participants', EventParticipantController::class);
        Route::post('events/{event}/participants/{participant}/entries', [ParticipantSportEntryController::class, 'store'])
            ->name('events.participants.entries.store');
        Route::delete('events/{event}/participants/{participant}/entries/{entry}', [ParticipantSportEntryController::class, 'destroy'])
            ->name('events.participants.entries.destroy');
        Route::resource('events.sports', SportController::class);
        Route::resource('events.athletes', AthleteController::class);
        Route::resource('events.officials', OfficialController::class);
        Route::resource('events.teams', TeamController::class);
        Route::resource('events.accreditations', AccreditationController::class);
        Route::get('events/{event}/accreditations/{accreditation}/badge', [AccreditationBadgeController::class, 'download'])
            ->name('events.accreditations.badge');
        Route::resource('events.competitions', CompetitionController::class);
        Route::post('events/{event}/competitions/{competition}/groups', [CompetitionController::class, 'storeGroup'])
            ->name('events.competitions.groups.store');
        Route::post('events/{event}/competitions/{competition}/fixtures', [CompetitionController::class, 'storeFixture'])
            ->name('events.competitions.fixtures.store');
        Route::delete('events/{event}/competitions/{competition}/fixtures/{fixture}', [CompetitionController::class, 'destroyFixture'])
            ->name('events.competitions.fixtures.destroy');
        Route::post('events/{event}/competitions/{competition}/fixtures/{fixture}/matches', [CompetitionController::class, 'storeMatch'])
            ->name('events.competitions.matches.store');
        Route::patch('events/{event}/competitions/{competition}/fixtures/{fixture}/matches/{matchGame}', [CompetitionController::class, 'updateMatch'])
            ->name('events.competitions.matches.update');
        Route::patch('events/{event}/competitions/{competition}/fixtures/{fixture}/matches/{matchGame}/officials', [CompetitionController::class, 'updateMatchOfficials'])
            ->name('events.competitions.matches.officials.update');
        Route::delete('events/{event}/competitions/{competition}/fixtures/{fixture}/matches/{matchGame}', [CompetitionController::class, 'destroyMatch'])
            ->name('events.competitions.matches.destroy');
        Route::get('events/{event}/schedule', [ScheduleController::class, 'index'])
            ->name('events.schedule.index');
        Route::post('events/{event}/competitions/{competition}/draw', [CompetitionController::class, 'generateDraw'])
            ->name('events.competitions.draw');
        Route::post('events/{event}/competitions/{competition}/knockout-phase', [CompetitionController::class, 'generateKnockoutPhase'])
            ->name('events.competitions.knockout-phase');
        Route::post('events/{event}/competitions/{competition}/fixtures/{fixture}/matches/{matchGame}/result', [ResultController::class, 'store'])
            ->name('events.competitions.matches.result.store');
        Route::patch('events/{event}/results/{result}/status', [ResultController::class, 'updateStatus'])
            ->name('events.results.status');
        Route::post('events/{event}/results/{result}/appeals', [ResultAppealController::class, 'store'])
            ->name('events.results.appeals.store');
        Route::patch('events/{event}/appeals/{appeal}/status', [ResultAppealController::class, 'updateStatus'])
            ->name('events.appeals.status');
        Route::get('events/{event}/rankings', [RankingController::class, 'index'])
            ->name('events.rankings.index');
        Route::get('events/{event}/medals', [MedalController::class, 'index'])
            ->name('events.medals.index');
        Route::get('events/{event}/medal-ceremonies', [MedalCeremonyController::class, 'index'])
            ->name('events.medal-ceremonies.index');
        Route::post('events/{event}/medal-ceremonies', [MedalCeremonyController::class, 'store'])
            ->name('events.medal-ceremonies.store');
        Route::delete('events/{event}/medal-ceremonies/{ceremony}', [MedalCeremonyController::class, 'destroy'])
            ->name('events.medal-ceremonies.destroy');
        Route::post('events/{event}/teams/{team}/athletes', [TeamController::class, 'storeAthlete'])
            ->name('events.teams.athletes.store');
        Route::delete('events/{event}/teams/{team}/athletes/{athlete}', [TeamController::class, 'destroyAthlete'])
            ->name('events.teams.athletes.destroy');
        Route::patch('events/{event}/registrations/{registration}/status', [RegistrationController::class, 'updateStatus'])
            ->name('events.registrations.status');
        Route::post('events/{event}/sports/{sport}/disciplines', [SportController::class, 'storeDiscipline'])
            ->name('events.sports.disciplines.store');
        Route::delete('events/{event}/sports/{sport}/disciplines/{discipline}', [SportController::class, 'destroyDiscipline'])
            ->name('events.sports.disciplines.destroy');
        Route::post('events/{event}/sports/{sport}/disciplines/{discipline}/categories', [SportController::class, 'storeCategory'])
            ->name('events.sports.categories.store');
        Route::delete('events/{event}/sports/{sport}/disciplines/{discipline}/categories/{category}', [SportController::class, 'destroyCategory'])
            ->name('events.sports.categories.destroy');
        Route::post('events/{event}/sports/{sport}/disciplines/{discipline}/categories/{category}/divisions', [SportController::class, 'storeDivision'])
            ->name('events.sports.divisions.store');
        Route::delete('events/{event}/sports/{sport}/disciplines/{discipline}/categories/{category}/divisions/{division}', [SportController::class, 'destroyDivision'])
            ->name('events.sports.divisions.destroy');
        Route::post('events/{event}/assignments', [EventController::class, 'storeAssignment'])
            ->name('events.assignments.store');
        Route::delete('events/{event}/assignments/{user}', [EventController::class, 'destroyAssignment'])
            ->name('events.assignments.destroy');
        Route::resource('venues', VenueController::class);
        Route::post('venues/{venue}/facilities', [FacilityController::class, 'store'])
            ->name('venues.facilities.store');
        Route::delete('venues/{venue}/facilities/{facility}', [FacilityController::class, 'destroy'])
            ->name('venues.facilities.destroy');
        Route::get('events/{event}/venues', [EventVenueController::class, 'index'])
            ->name('events.venues.index');
        Route::get('events/{event}/venues/{venue}', [EventVenueController::class, 'show'])
            ->name('events.venues.show');
        Route::post('events/{event}/venues', [EventVenueController::class, 'store'])
            ->name('events.venues.store');
        Route::delete('events/{event}/venues/{venue}', [EventVenueController::class, 'destroy'])
            ->name('events.venues.destroy');
        Route::post('events/{event}/venues/{venue}/sports', [EventVenueController::class, 'storeSport'])
            ->name('events.venues.sports.store');
        Route::delete('events/{event}/venues/{venue}/sports/{sport}', [EventVenueController::class, 'destroySport'])
            ->name('events.venues.sports.destroy');
    });

    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('organizations', OrganizationController::class)->except(['show']);
        Route::post('organizations/{organization}/branches', [OrganizationController::class, 'storeBranch'])
            ->name('organizations.branches.store');
        Route::delete('organizations/{organization}/branches/{branch}', [OrganizationController::class, 'destroyBranch'])
            ->name('organizations.branches.destroy');
        Route::resource('users', UserController::class)->except(['show']);
    });
});

require __DIR__.'/auth.php';
