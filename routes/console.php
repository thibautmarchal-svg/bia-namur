<?php

use App\Jobs\GenerateBriefJob;
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
| En prod, cron-job.org ping toutes les minutes l'endpoint
|   /_deploy/schedule?secret=BIA_DEPLOY_SECRET
| qui appelle `php artisan schedule:run`. Laravel decide ensuite quels
| jobs reveiller en fonction des declarations ci-dessous.
|
| En local : `php artisan schedule:work` dans un terminal dedie, ou les
| commandes manuelles `bia:brief:generate-test`.
|
| J27 — desactive : IngestOpenDataJob, IngestRssJob, NormalizeEventsJob.
| Toutes les sources qu'ils ciblent sont mortes (OpenData v2 gele en
| 2018, RSS Delta/Belvedere/Theatre Royal en 404). Le seul flux qui
| fonctionne est namur.be/fr/agenda/agenda/RSS, consomme directement
| par GenerateBriefJob via NamurAgendaRssService — pas besoin d'une
| ingestion intermediaire en DB.
*/

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

// Worker queue : execute les jobs en attente (SendBriefValidationNotifJob,
// SendBriefPublishedNotificationJob, ModerateContributionJob, etc.).
// --stop-when-empty : sort des qu'il n'y a plus rien en queue (sinon le
//   process tourne indefiniment, incompatible avec mutualise sans SSH).
// --max-time=50 : safety net pour ne pas depasser le timeout PHP (60s par
//   defaut sur la plupart des shared hostings).
// Appele chaque minute par le ping cron-job.org qui declenche schedule:run.
Schedule::command('queue:work --stop-when-empty --tries=3 --max-time=50 --max-jobs=20')
    ->name('queue:work')
    ->everyMinute()
    ->withoutOverlapping()
    ->onOneServer();

/*
| Auto-publish brief si pas relu : a activer plus tard quand on aura
| confiance dans la qualite Claude. Pour l'instant le brief reste en
| draft_ai jusqu'a relecture humaine via Filament.
*/
