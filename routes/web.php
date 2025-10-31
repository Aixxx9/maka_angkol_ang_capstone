<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SchoolController;
use App\Http\Controllers\SportController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\LiveController;
use App\Http\Controllers\AthleteController;
use App\Http\Controllers\MatchesController;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Public
|--------------------------------------------------------------------------
*/

Route::get('/', [NewsController::class, 'home'])->name('home');

// School routes
Route::get('/schools', [SchoolController::class, 'index'])->name('schools.index');
Route::post('/schools', [SchoolController::class, 'store'])->name('schools.store');
Route::get('/schools/{slug}', [SchoolController::class, 'show'])->name('schools.show');
Route::get('/schools/{slug}/edit', [SchoolController::class, 'edit'])->name('schools.edit');
Route::delete('/schools/{slug}', [SchoolController::class, 'destroy'])->name('schools.destroy');
Route::put('/schools/{slug}', [SchoolController::class, 'update'])->name('schools.update');
Route::post('/schools/{slug}', [SchoolController::class, 'update'])->name('schools.update.post');

/*
|--------------------------------------------------------------------------
| Sports Routes
|--------------------------------------------------------------------------
|
| IMPORTANT: The /sports/create route must come BEFORE the resource route.
| Otherwise, Laravel thinks “create” is a {sport} slug → 404 error.
|
*/

// ✅ Public route for creating sports
Route::get('/sports/create', [App\Http\Controllers\SportController::class, 'create'])->name('sports.create');

// ✅ Public route for storing sports
Route::post('/sports', [App\Http\Controllers\SportController::class, 'store'])->name('sports.store');

// ✅ Sports list + show
Route::resource('sports', App\Http\Controllers\SportController::class)
    ->only(['index', 'show'])
    ->names([
        'index' => 'sports.index',
        'show'  => 'sports.show',
    ]);

    Route::delete('/sports/{sport}', [SportController::class, 'destroy'])->name('sports.destroy');

    Route::get('/sports/{sport}/edit', [SportController::class, 'edit'])->name('sports.edit');
Route::put('/sports/{sport}', [SportController::class, 'update'])->name('sports.update');


/*
|--------------------------------------------------------------------------
| Games
|--------------------------------------------------------------------------
*/
Route::get('/schedule', [GameController::class, 'schedule'])->name('schedule');
Route::get('/games/create', [GameController::class, 'create'])->name('games.create');
Route::post('/games', [GameController::class, 'store'])->name('games.store');
Route::get('/games/{game}', [GameController::class, 'show'])
    ->whereNumber('game')
    ->name('games.show');

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

/*
|--------------------------------------------------------------------------
| News (public)
|--------------------------------------------------------------------------
*/
Route::get('/news', fn() => Inertia::render('News/Index'))->name('news.index');
Route::get('/news/create', fn() => Inertia::render('News/Create'))->name('news.create');

/*
|--------------------------------------------------------------------------
| Videos (public)
|--------------------------------------------------------------------------
*/
Route::get('/videos', fn() => Inertia::render('Videos/Index'))->name('videos.index');

/*
|--------------------------------------------------------------------------
| Athletes
|--------------------------------------------------------------------------
*/
Route::get('/athletes', [AthleteController::class, 'index'])->name('athletes.index');
Route::post('/athletes', [AthleteController::class, 'store'])->name('athletes.store');

/*
|--------------------------------------------------------------------------
| Admin / Mod Routes
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->middleware(['auth','role:super-admin,mod'])->group(function () {
    // News Desk
    Route::get('news',             [NewsController::class, 'adminIndex'])->name('news.index');
    Route::get('news/create',      [NewsController::class, 'create'])->name('news.create');
    Route::post('news',            [NewsController::class, 'store'])->name('news.store');
    Route::get('news/{news}/edit', [NewsController::class, 'edit'])->name('news.edit');
    Route::put('news/{news}',      [NewsController::class, 'update'])->name('news.update');
    Route::delete('news/{news}',   [NewsController::class, 'destroy'])->name('news.destroy');

    // Games CRUD + live controls (admin-scoped)
    Route::resource('games', GameController::class)->except(['show']);
    Route::post('games/{game}/events', [GameController::class,'addEvent'])->name('games.events');
    Route::post('games/{game}/status', [GameController::class,'setStatus'])->name('games.status');
    Route::post('games/{game}/embed',  [GameController::class,'setEmbed'])->name('games.embed');

    // Documents upload
    Route::post('documents', [DocumentController::class,'store'])->name('documents.store');
});

/*
|--------------------------------------------------------------------------
| Super Admin Only (system objects)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->middleware(['auth','role:super-admin'])->group(function () {
    Route::resource('schools', SchoolController::class)->except(['index','show']);
    Route::resource('sports',  SportController::class)->except(['index','show']);
});

require __DIR__.'/auth.php';
