<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    SchoolController,
    SportController,
    GameController,
    NewsController,
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

/*
|--------------------------------------------------------------------------
| Schools
|--------------------------------------------------------------------------
*/
Route::get('/schools', [SchoolController::class, 'index'])->name('schools.index');
Route::post('/schools', [SchoolController::class, 'store'])->name('schools.store');
Route::get('/schools/{slug}', [SchoolController::class, 'show'])->name('schools.show');
Route::get('/schools/{slug}/edit', [SchoolController::class, 'edit'])->name('schools.edit');
Route::put('/schools/{slug}', [SchoolController::class, 'update'])->name('schools.update');
Route::delete('/schools/{slug}', [SchoolController::class, 'destroy'])->name('schools.destroy');

/*
|--------------------------------------------------------------------------
| Sports
|--------------------------------------------------------------------------
*/
Route::get('/sports', [SportController::class, 'index'])->name('sports.index');
Route::get('/sports/create', [SportController::class, 'create'])->name('sports.create');
Route::post('/sports', [SportController::class, 'store'])->name('sports.store');
Route::get('/sports/{sport}', [SportController::class, 'show'])->name('sports.show');
Route::get('/sports/{sport}/edit', [SportController::class, 'edit'])->name('sports.edit');
Route::put('/sports/{sport}', [SportController::class, 'update'])->name('sports.update');
Route::delete('/sports/{sport}', [SportController::class, 'destroy'])->name('sports.destroy');

/*
|--------------------------------------------------------------------------
| Games (PUBLIC CRUD)
|--------------------------------------------------------------------------
*/
Route::get('/schedule', [GameController::class, 'schedule'])->name('schedule');
Route::get('/games/create', [GameController::class, 'create'])->name('games.create');
Route::post('/games', [GameController::class, 'store'])->name('games.store');
Route::get('/games/{game}', [GameController::class, 'show'])
    ->whereNumber('game')
    ->name('games.show');

// ✅ Added edit, update, delete publicly
Route::get('/games/{game}/edit', [GameController::class, 'edit'])->name('games.edit');
Route::put('/games/{game}', [GameController::class, 'update'])->name('games.update');
Route::delete('/games/{game}', [GameController::class, 'destroy'])->name('games.destroy');

/*
|--------------------------------------------------------------------------
| Live
|--------------------------------------------------------------------------
*/
Route::get('/live/{game}', [LiveController::class, 'show'])->name('live.show');

/*
|--------------------------------------------------------------------------
| Documents
|--------------------------------------------------------------------------
*/
Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
Route::get('/documents/{document}', [DocumentController::class, 'download'])->name('documents.download');
Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');

/*
|--------------------------------------------------------------------------
| News
|--------------------------------------------------------------------------
*/
Route::get('/news', [NewsController::class, 'index'])->name('news.index');
Route::get('/news/create', [NewsController::class, 'create'])->name('news.create');
Route::post('/news', [NewsController::class, 'store'])->name('news.store');
Route::get('/news/{news}', [NewsController::class, 'show'])->name('news.show');
Route::get('/news/{news}/edit', [NewsController::class, 'edit'])->name('news.edit');
Route::put('/news/{news}', [NewsController::class, 'update'])->name('news.update');
Route::delete('/news/{news}', [NewsController::class, 'destroy'])->name('news.destroy');

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
Route::get('/athletes/create', [AthleteController::class, 'create'])->name('athletes.create');
Route::post('/athletes', [AthleteController::class, 'store'])->name('athletes.store');
Route::post('/athletes/{athlete}/game-stats', [AthleteController::class, 'storeGameStat'])
    ->name('athletes.stats.store');
Route::delete('/athletes/{athlete}/game-stats/{stat}', [AthleteController::class, 'destroyGameStat'])
    ->name('athletes.stats.destroy');

require __DIR__.'/auth.php';
