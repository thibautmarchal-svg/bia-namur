<?php

use App\Http\Controllers\Auth\MagicLinkController;
use App\Http\Controllers\BriefController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\PlaceController;
use App\Http\Controllers\StoryController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Pages publiques editoriales
Route::get('/', HomeController::class)->name('home');
Route::get('/briefs', [BriefController::class, 'index'])->name('briefs.index');
Route::get('/brief/{slug}', [BriefController::class, 'show'])->name('briefs.show');
Route::get('/lieux', [PlaceController::class, 'index'])->name('places.index');
Route::get('/lieu/{slug}', [PlaceController::class, 'show'])->name('places.show');
Route::get('/stories', [StoryController::class, 'index'])->name('stories.index');
Route::get('/story/{slug}', [StoryController::class, 'show'])->name('stories.show');
Route::get('/carte', MapController::class)->name('map');

// Auth magic link
Route::middleware('guest')->group(function () {
    Route::get('/login', [MagicLinkController::class, 'showLogin'])->name('login');
    Route::post('/auth/magic-link', [MagicLinkController::class, 'request'])
        ->middleware('throttle:6,60')
        ->name('auth.magic-link.request');
    Route::get('/auth/magic-link/{token}', [MagicLinkController::class, 'consume'])
        ->middleware('throttle:10,60')
        ->name('auth.magic-link.consume')
        ->where('token', '[A-Za-z0-9]{64}');
});

Route::post('/logout', [MagicLinkController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// Page de demo composants UI — uniquement en environnement local
if (app()->environment('local')) {
    Route::get('/dev/components', fn () => Inertia::render('Dev/Components'))
        ->name('dev.components');
}
