<?php

use App\Http\Controllers\Api\V1\AthleteController;
use App\Http\Controllers\Api\V1\EventParticipantController;
use App\Http\Controllers\Api\V1\CompetitionController;
use App\Http\Controllers\Api\V1\OfficialController;
use App\Http\Controllers\Api\V1\AuditLogController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\MedalController;
use App\Http\Controllers\Api\V1\RankingController;
use App\Http\Controllers\Api\V1\RegistrationController;
use App\Http\Controllers\Api\V1\ResultAppealController;
use App\Http\Controllers\Api\V1\ResultController;
use App\Http\Controllers\Api\V1\ScheduleController;
use App\Http\Controllers\Api\V1\EventController;
use App\Http\Controllers\Api\V1\EventVenueController;
use App\Http\Controllers\Api\V1\OrganizationController;
use App\Http\Controllers\Api\V1\ParticipantSportEntryController;
use App\Http\Controllers\Api\V1\SportController;
use App\Http\Controllers\Api\V1\TeamController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\VenueController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:auth');

    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
    });
});

Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::apiResource('organizations', OrganizationController::class);
    Route::get('organizations/{organization}/branches', [OrganizationController::class, 'branches']);

    Route::apiResource('users', UserController::class);
    Route::apiResource('events', EventController::class);
    Route::patch('events/{event}/status', [EventController::class, 'updateStatus']);
    Route::apiResource('events.sports', SportController::class);
    Route::post('events/{event}/participants/import', [EventParticipantController::class, 'import']);
    Route::apiResource('events.participants', EventParticipantController::class);
    Route::post('events/{event}/participants/{participant}/entries', [ParticipantSportEntryController::class, 'store']);
    Route::delete('events/{event}/participants/{participant}/entries/{entry}', [ParticipantSportEntryController::class, 'destroy']);
    Route::apiResource('events.athletes', AthleteController::class);
    Route::apiResource('events.officials', OfficialController::class);
    Route::apiResource('events.teams', TeamController::class);
    Route::apiResource('events.competitions', CompetitionController::class);
    Route::get('competition-formats', [CompetitionController::class, 'formats']);
    Route::post('events/{event}/competitions/{competition}/fixtures', [CompetitionController::class, 'storeFixture']);
    Route::post('events/{event}/competitions/{competition}/fixtures/{fixture}/matches', [CompetitionController::class, 'storeMatch']);
    Route::put('events/{event}/competitions/{competition}/fixtures/{fixture}/matches/{matchGame}', [CompetitionController::class, 'updateMatch']);
    Route::get('events/{event}/schedule', [ScheduleController::class, 'index']);
    Route::post('events/{event}/competitions/{competition}/draw', [CompetitionController::class, 'generateDraw']);
    Route::post('events/{event}/competitions/{competition}/knockout-phase', [CompetitionController::class, 'generateKnockoutPhase']);
    Route::get('events/{event}/competitions/{competition}/bracket', [CompetitionController::class, 'bracket']);
    Route::post('events/{event}/matches/{matchGame}/result', [ResultController::class, 'store']);
    Route::patch('results/{result}/status', [ResultController::class, 'updateStatus']);
    Route::post('results/{result}/appeals', [ResultAppealController::class, 'store']);
    Route::patch('appeals/{appeal}/status', [ResultAppealController::class, 'updateStatus']);
    Route::get('events/{event}/rankings', [RankingController::class, 'index']);
    Route::get('events/{event}/medals', [MedalController::class, 'index']);
    Route::post('events/{event}/teams/{team}/athletes', [TeamController::class, 'storeAthlete']);
    Route::delete('events/{event}/teams/{team}/athletes/{athlete}', [TeamController::class, 'destroyAthlete']);
    Route::patch('events/{event}/registrations/{registration}/status', [RegistrationController::class, 'updateStatus']);
    Route::apiResource('venues', VenueController::class);
    Route::post('venues/{venue}/facilities', [VenueController::class, 'storeFacility']);
    Route::delete('venues/{venue}/facilities/{facility}', [VenueController::class, 'destroyFacility']);
    Route::get('events/{event}/venues', [EventVenueController::class, 'index']);
    Route::post('events/{event}/venues', [EventVenueController::class, 'store']);
    Route::get('events/{event}/venues/{venue}', [EventVenueController::class, 'show']);
    Route::delete('events/{event}/venues/{venue}', [EventVenueController::class, 'destroy']);
    Route::post('events/{event}/venues/{venue}/sports', [EventVenueController::class, 'storeSport']);
    Route::delete('events/{event}/venues/{venue}/sports/{sport}', [EventVenueController::class, 'destroySport']);

    Route::get('audit-logs', [AuditLogController::class, 'index']);
});