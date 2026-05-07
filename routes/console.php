<?php

use App\Jobs\GenerateBriefJob;
use App\Jobs\IngestOpenDataJob;
use App\Jobs\IngestRssJob;
use App\Jobs\NormalizeEventsJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Bia Namur — schedule des pipelines
|--------------------------------------------------------------------------
|
| En production (S3+) un cron mutualise lance 'php artisan schedule:run'
| toutes les minutes — Laravel decide ensuite quels jobs reveiller en
| fonction des declarations ci-dessous.
|
| En local : on n'a pas le cron, donc tu peux soit :
|  - lancer 'php artisan schedule:work' dans un terminal dedie pour
|    simuler le cron mutualise,
|  - soit utiliser les commandes manuelles 'bia:ingest:run' et
|    'bia:brief:generate-test' a la demande.
*/

// Ingestion OpenData Namur — toutes les heures pile, sans overlap
Schedule::job(new IngestOpenDataJob('namur'))
    ->name('ingest:opendata-namur')
    ->hourly()
    ->withoutOverlapping()
    ->onOneServer();

// Ingestion RSS feeds (Le Delta, Belvedere, Theatre Royal) — HH:05
Schedule::job(new IngestRssJob('namur'))
    ->name('ingest:rss')
    ->hourlyAt(5)
    ->withoutOverlapping()
    ->onOneServer();

// Normalisation des events ingerés — HH:15, apres les 2 ingestions
Schedule::job(new NormalizeEventsJob('namur'))
    ->name('events:normalize')
    ->hourlyAt(15)
    ->withoutOverlapping()
    ->onOneServer();

// Brief hebdo Bia Namur — vendredi 14h00 Europe/Brussels
Schedule::call(function () {
    $now = now('Europe/Brussels');
    dispatch(new GenerateBriefJob('namur', $now->year, $now->isoWeek()));
})
    ->name('brief:generate-weekly')
    ->fridays()
    ->at('14:00')
    ->timezone('Europe/Brussels')
    ->withoutOverlapping()
    ->onOneServer();

/*
| Auto-publish brief si pas relu : a activer en S2 quand l'auth admin
| sera stable + qu'on aura le flag config('bia.ai.auto_publish_brief').
| Pour l'instant le brief reste en draft_ai jusqu'a relecture humaine.
*/
