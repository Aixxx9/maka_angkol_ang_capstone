<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\EnsureRole;
use App\Http\Controllers\{
    SchoolController,
    SportController,
    GameController,
    NewsController,
    MatchesController,
    StandingsController,
    BracketController,
    DocumentController,
    LiveController,
    AthleteController,
    HomeController
};
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', [HomeController::class, 'index'])->name('home');
// Fallback for Breeze's default post-login redirect
Route::get('/dashboard', fn() => redirect()->route('home'))->name('dashboard');

/*
|--------------------------------------------------------------------------
| Schools
|--------------------------------------------------------------------------
*/
Route::get('/schools', [SchoolController::class, 'index'])->name('schools.index');
Route::get('/schools/{slug}', [SchoolController::class, 'show'])->name('schools.show');

/*
|--------------------------------------------------------------------------
| Sports
|--------------------------------------------------------------------------
*/
Route::get('/sports', [SportController::class, 'index'])->name('sports.index');
Route::get('/sports/{sport}', [SportController::class, 'show'])->name('sports.show');

/*
|--------------------------------------------------------------------------
| Games
|--------------------------------------------------------------------------
*/
Route::get('/schedule', [GameController::class, 'schedule'])->name('schedule');
Route::get('/games/{game}', [GameController::class, 'show'])
    ->whereNumber('game')
    ->name('games.show');

/*
|--------------------------------------------------------------------------
| Matches / Standings
|--------------------------------------------------------------------------
*/
Route::get('/matches', [MatchesController::class, 'index'])->name('matches.index');
Route::get('/standings', [StandingsController::class, 'index'])->name('standings.index');

/*
|--------------------------------------------------------------------------
| Live
|--------------------------------------------------------------------------
*/
Route::get('/live/{game}', [LiveController::class, 'show'])
    ->whereNumber('game')
    ->name('live.show');

/*
|--------------------------------------------------------------------------
| Documents
|--------------------------------------------------------------------------
*/
Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
Route::get('/documents/{document}', [DocumentController::class, 'download'])->name('documents.download');

/*
|--------------------------------------------------------------------------
| News
|--------------------------------------------------------------------------
*/
Route::get('/news', [NewsController::class, 'index'])->name('news.index');
Route::get('/news/{news}', [NewsController::class, 'show'])->name('news.show');

/*
|--------------------------------------------------------------------------
| Videos
|--------------------------------------------------------------------------
*/
Route::get('/videos', fn() => Inertia::render('Videos/Index'))->name('videos.index');

/*
|--------------------------------------------------------------------------
| Athletes
|--------------------------------------------------------------------------
*/
Route::get('/athletes', [AthleteController::class, 'index'])->name('athletes.index');

/*
|--------------------------------------------------------------------------
| Admin-only routes (auth + role:admin)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', EnsureRole::class . ':admin,super-admin'])->group(function () {
    // Schools (manage)
    Route::post('/schools', [SchoolController::class, 'store'])->name('schools.store');
    Route::get('/schools/{slug}/edit', [SchoolController::class, 'edit'])->name('schools.edit');
    Route::put('/schools/{slug}', [SchoolController::class, 'update'])->name('schools.update');
    Route::delete('/schools/{slug}', [SchoolController::class, 'destroy'])->name('schools.destroy');

    // Sports (manage)
    Route::get('/sports/create', [SportController::class, 'create'])->name('sports.create');
    Route::post('/sports', [SportController::class, 'store'])->name('sports.store');
    Route::get('/sports/{sport}/edit', [SportController::class, 'edit'])->name('sports.edit');
    Route::put('/sports/{sport}', [SportController::class, 'update'])->name('sports.update');
    Route::delete('/sports/{sport}', [SportController::class, 'destroy'])->name('sports.destroy');

    // Games (manage)
    Route::get('/games/create', [GameController::class, 'create'])->name('games.create');
    Route::post('/games', [GameController::class, 'store'])->name('games.store');
    Route::get('/games/{game}/edit', [GameController::class, 'edit'])->name('games.edit');
    Route::put('/games/{game}', [GameController::class, 'update'])->name('games.update');
    Route::delete('/games/{game}', [GameController::class, 'destroy'])->name('games.destroy');
    Route::put('/games/{game}/finalize', [GameController::class, 'finalize'])->name('games.finalize');
    Route::post('/games/{game}/events', [GameController::class, 'addEvent'])->name('games.events.add');

    // Brackets (manage)
    Route::get('/brackets/create', [BracketController::class, 'create'])->name('brackets.create');
    Route::post('/brackets', [BracketController::class, 'store'])->name('brackets.store');
    Route::get('/brackets/{competition}/builder', [BracketController::class, 'builder'])->name('brackets.builder');

    // Documents (manage uploads)
    Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');

    // News (manage)
    Route::get('/news/create', [NewsController::class, 'create'])->name('news.create');
    Route::post('/news', [NewsController::class, 'store'])->name('news.store');
    Route::get('/news/{news}/edit', [NewsController::class, 'edit'])->name('news.edit');
    Route::put('/news/{news}', [NewsController::class, 'update'])->name('news.update');
    Route::delete('/news/{news}', [NewsController::class, 'destroy'])->name('news.destroy');

    // Athletes (manage)
    Route::get('/athletes/create', [AthleteController::class, 'create'])->name('athletes.create');
    Route::post('/athletes', [AthleteController::class, 'store'])->name('athletes.store');
    Route::post('/athletes/{athlete}/game-stats', [AthleteController::class, 'storeGameStat'])->name('athletes.stats.store');
    Route::delete('/athletes/{athlete}/game-stats/{stat}', [AthleteController::class, 'destroyGameStat'])->name('athletes.stats.destroy');

    // Live (manage)
    Route::get('/live/create', [LiveController::class, 'create'])->name('live.create');
    Route::post('/live', [LiveController::class, 'store'])->name('live.store');
    Route::put('/live/{game}/stop', [LiveController::class, 'stop'])->name('live.stop');
});

require __DIR__.'/auth.php';
