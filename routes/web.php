<?php

use App\Http\Controllers\Auth\MagicLinkController;
use App\Http\Controllers\BriefController;
use App\Http\Controllers\ContributionController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PlaceController;
use App\Http\Controllers\SearchController;
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
Route::get('/recherche', SearchController::class)->name('search');

// Contribution publique : form + submit + page de remerciement
Route::get('/contribuer', [ContributionController::class, 'form'])->name('contribute.form');
Route::post('/contribuer', [ContributionController::class, 'store'])
    ->middleware('throttle:6,60')
    ->name('contribute.store');
Route::get('/contribuer/merci', [ContributionController::class, 'thanks'])->name('contribute.thanks');

// Pages éditoriales statiques
Route::get('/wallon', [PageController::class, 'wallon'])->name('wallon');
Route::get('/a-propos', [PageController::class, 'about'])->name('about');

// Pages légales
Route::get('/mentions-legales', [PageController::class, 'legalMentions'])->name('legal.mentions');
Route::get('/cgu', [PageController::class, 'legalTerms'])->name('legal.terms');
Route::get('/confidentialite', [PageController::class, 'legalPrivacy'])->name('legal.privacy');

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

// Favoris (auth requise)
Route::middleware('auth')->group(function () {
    Route::get('/mes-favoris', [FavoriteController::class, 'index'])->name('favorites.index');
    Route::post('/favoris/toggle', [FavoriteController::class, 'toggle'])
        ->middleware('throttle:60,1')
        ->name('favorites.toggle');
});

// Page de demo composants UI — uniquement en environnement local
if (app()->environment('local')) {
    Route::get('/dev/components', fn () => Inertia::render('Dev/Components'))
        ->name('dev.components');
}
