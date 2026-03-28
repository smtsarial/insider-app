<?php

use App\Http\Controllers\LeagueController;
use App\Models\LeagueState;
use Illuminate\Support\Facades\Route;

Route::get('/', [LeagueController::class, 'home'])->name('home');

Route::get('/league', function () {
    $step = LeagueState::current()->step;

    return match ($step) {
        'fixtures' => redirect()->route('league.fixtures'),
        'simulation' => redirect()->route('league.simulation'),
        default => redirect()->route('home'),
    };
})->name('league.index');

Route::prefix('league')->name('league.')->group(function () {
    Route::get('/tournament-teams', [LeagueController::class, 'tournamentTeams'])->name('tournament-teams');
    Route::post('/fixtures', [LeagueController::class, 'storeFixtures'])->name('fixtures.store');
    Route::get('/fixtures', [LeagueController::class, 'fixtures'])->name('fixtures');
    Route::post('/simulation/start', [LeagueController::class, 'startSimulation'])->name('simulation.start');
    Route::get('/simulation', [LeagueController::class, 'simulation'])->name('simulation');
    Route::post('/simulation/view-week', [LeagueController::class, 'setViewWeek'])->name('simulation.view-week');
    Route::post('/simulation/play-next', [LeagueController::class, 'playNextWeek'])->name('simulation.play-next');
    Route::post('/simulation/play-all', [LeagueController::class, 'playAllWeeks'])->name('simulation.play-all');
    Route::post('/reset', [LeagueController::class, 'reset'])->name('reset');
    Route::patch('/matches/{league_match}', [LeagueController::class, 'updateMatch'])->name('matches.update');
});
