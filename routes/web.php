<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Auth\MagicLinkController;
use App\Http\Controllers\BriefController;
use App\Http\Controllers\ContributionController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PlaceController;
use App\Http\Controllers\PushController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\StoryController;
use App\Http\Middleware\RecordPageView;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Pages publiques editoriales
Route::get('/', HomeController::class)->name('home');
Route::get('/briefs', [BriefController::class, 'index'])->name('briefs.index');
Route::get('/lieux', [PlaceController::class, 'index'])->name('places.index');
Route::get('/stories', [StoryController::class, 'index'])->name('stories.index');
Route::get('/carte', MapController::class)->name('map');

// Pages show : tracking interne via RecordPageView (anonymise + dedup 24h)
Route::middleware(RecordPageView::class)->group(function () {
    Route::get('/brief/{slug}', [BriefController::class, 'show'])->name('briefs.show');
    Route::get('/lieu/{slug}', [PlaceController::class, 'show'])->name('places.show');
    Route::get('/story/{slug}', [StoryController::class, 'show'])->name('stories.show');
});
Route::get('/recherche', SearchController::class)->name('search');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

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

    // Push notifications (subscribe = stockage endpoint apres opt-in explicite)
    Route::post('/push/subscribe', [PushController::class, 'subscribe'])
        ->middleware('throttle:10,1')
        ->name('push.subscribe');
    Route::post('/push/unsubscribe', [PushController::class, 'unsubscribe'])
        ->middleware('throttle:10,1')
        ->name('push.unsubscribe');

    // Compte utilisateur + RGPD (export, suppression)
    Route::get('/mon-compte', [AccountController::class, 'show'])->name('account.show');
    Route::put('/mon-compte', [AccountController::class, 'update'])->name('account.update');
    Route::get('/me/export', [AccountController::class, 'export'])
        ->middleware('throttle:5,60')
        ->name('account.export');
    Route::post('/me/delete', [AccountController::class, 'destroy'])
        ->middleware('throttle:3,60')
        ->name('account.destroy');
});

// Page de demo composants UI — uniquement en environnement local
if (app()->environment('local')) {
    Route::get('/dev/components', fn () => Inertia::render('Dev/Components'))
        ->name('dev.components');
}

// ─────────────────────────────────────────────────────────────────────
// Endpoints de deploiement (hebergement sans SSH)
// Proteges par BIA_DEPLOY_SECRET (dans .env, jamais commit, generer
// via `openssl rand -hex 32`). Si le secret est absent : 404 silencieux.
// Rate-limit serre pour eviter le brute-force.
// ─────────────────────────────────────────────────────────────────────

Route::post('/_deploy/migrate', function () {
    $secret = request()->input('secret');
    if (! $secret || $secret !== config('bia.deploy.secret')) {
        abort(404);
    }

    Artisan::call('migrate', ['--force' => true]);

    return response('<pre>' . Artisan::output() . '</pre>')
        ->header('Content-Type', 'text/html; charset=utf-8');
})->middleware('throttle:3,60');

Route::post('/_deploy/cache', function () {
    $secret = request()->input('secret');
    if (! $secret || $secret !== config('bia.deploy.secret')) {
        abort(404);
    }

    $output = '';
    foreach (['config:cache', 'route:cache', 'view:cache', 'event:cache'] as $cmd) {
        Artisan::call($cmd);
        $output .= "[$cmd]\n" . Artisan::output() . "\n";
    }

    // Reset OPcache pour que le nouveau code soit pris en compte (alternative
    // au reload PHP-FPM qui necessite sudo).
    if (function_exists('opcache_reset')) {
        opcache_reset();
        $output .= "[opcache_reset] OK\n";
    }

    return response('<pre>' . e($output) . '</pre>')
        ->header('Content-Type', 'text/html; charset=utf-8');
})->middleware('throttle:3,60');

Route::post('/_deploy/storage-link', function () {
    $secret = request()->input('secret');
    if (! $secret || $secret !== config('bia.deploy.secret')) {
        abort(404);
    }

    Artisan::call('storage:link');

    return response('<pre>' . Artisan::output() . '</pre>')
        ->header('Content-Type', 'text/html; charset=utf-8');
})->middleware('throttle:3,60');

// Endpoint d'extraction d'archive de deploiement.
// Le workflow GitHub Actions upload un .tar.gz qui contient TOUT
// le code (Laravel + vendor + public/build), beaucoup plus rapide
// qu'uploader 10000 fichiers individuels en FTP.
// Cet endpoint l'extrait sur place, puis supprime l'archive.
Route::post('/_deploy/extract', function () {
    $secret = request()->input('secret');
    if (! $secret || $secret !== config('bia.deploy.secret')) {
        abort(404);
    }

    // PHP CLI memory limit override pour les gros extracts
    @ini_set('memory_limit', '512M');
    @set_time_limit(600);

    $archivePath = base_path('bia-deploy.tar.gz');
    if (! is_file($archivePath)) {
        return response('Archive introuvable : ' . $archivePath, 500);
    }

    $output = 'Archive trouvee : ' . filesize($archivePath) . " bytes\n";

    try {
        // PharData supporte tar.gz nativement, pas besoin de PECL
        $phar = new PharData($archivePath);
        $phar->extractTo(base_path(), null, true);
        $output .= "Extraction terminee\n";

        unlink($archivePath);
        $output .= "Archive supprimee\n";

        // Permissions Laravel critiques
        @chmod(base_path('storage'), 0775);
        @chmod(base_path('bootstrap/cache'), 0775);
        $output .= "Permissions storage/ et bootstrap/cache/ → 775\n";

        // OPcache reset pour que le nouveau code soit pris en compte
        if (function_exists('opcache_reset')) {
            opcache_reset();
            $output .= "OPcache reset OK\n";
        }

        return response('<pre>' . e($output) . '</pre>')
            ->header('Content-Type', 'text/html; charset=utf-8');
    } catch (Throwable $e) {
        return response(
            '<pre>Extraction failed: ' . e($e->getMessage()) . '</pre>',
            500,
        )->header('Content-Type', 'text/html; charset=utf-8');
    }
})->middleware('throttle:3,60');

// Endpoint scheduler pour cron externe (cron-job.org ou EasyCron).
// Appele toutes les minutes via GET/POST avec le secret. Laravel decide
// lui-meme si une tache doit s'executer a ce moment.
//
// IMPORTANT : on accepte GET ET POST. cron-job.org ping en GET par defaut,
// et si on repondait 405 Method Not Allowed, la page d'erreur Laravel
// depasse leur limite de taille de reponse ("Echec : sortie trop grande").
//
// Reponse volontairement minimale ("OK\n") : cron-job.org ne tronque pas
// les sorties courtes et leur dashboard reste lisible. Le detail de ce qui
// a tourne est dans les logs Laravel cote serveur.
Route::match(['get', 'post'], '/_deploy/schedule', function () {
    $secret = request()->input('secret');
    if (! $secret || $secret !== config('bia.deploy.secret')) {
        abort(404);
    }

    try {
        Artisan::call('schedule:run');
    } catch (Throwable $e) {
        Log::error('schedule.run_failed', ['error' => $e->getMessage()]);

        return response('ERR', 200)->header('Content-Type', 'text/plain');
    }

    return response('OK', 200)->header('Content-Type', 'text/plain');
})->middleware('throttle:120,1');
