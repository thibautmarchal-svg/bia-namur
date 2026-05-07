<?php

namespace App\Console\Commands;

use App\Jobs\IngestOpenDataJob;
use App\Jobs\NormalizeEventsJob;
use App\Models\Event;
use Illuminate\Console\Command;

/**
 * Lance les pipelines d'ingestion en synchrone, depuis la CLI.
 * En prod c'est le scheduler qui declenche IngestOpenDataJob hourly +
 * NormalizeEventsJob hourlyAt(15). En dev / debug, cette commande est
 * la facon la plus directe de remplir la table events depuis la fixture
 * (ou l'API reelle si BIA_INGEST_FIXTURE_MODE=false).
 *
 * Exemples :
 *   php artisan bia:ingest:run
 *   php artisan bia:ingest:run --skip-normalize
 *   php artisan bia:ingest:run --city=namur
 */
class IngestRunCommand extends Command
{
    protected $signature = 'bia:ingest:run
        {--city=namur : slug de la ville}
        {--skip-normalize : ne lance que IngestOpenDataJob, pas la normalisation}';

    protected $description = 'Lance les pipelines d\'ingestion (OpenData puis NormalizeEvents) en synchrone.';

    public function handle(): int
    {
        $city = $this->option('city');
        $mode = config('bia.ingestion.fixture_mode') ? 'FIXTURE' : 'API REELLE';

        $this->components->info("Mode ingestion : {$mode}");
        $this->components->info("Ville : {$city}");
        $this->newLine();

        // 1. Ingestion
        $this->components->task('Ingestion OpenData Namur', function () use ($city) {
            $job = new IngestOpenDataJob($city);
            app()->call([$job, 'handle']);

            return true;
        });

        if (! $this->option('skip-normalize')) {
            // 2. Normalisation
            $this->components->task('Normalisation events (geocoding + categorisation + dedup)', function () use ($city) {
                $job = new NormalizeEventsJob($city);
                app()->call([$job, 'handle']);

                return true;
            });
        }

        $this->newLine();
        $this->components->info('Ingestion terminée. État de la table events :');

        $stats = Event::query()
            ->selectRaw('source, status, COUNT(*) as count')
            ->groupBy('source', 'status')
            ->orderBy('source')
            ->orderBy('status')
            ->get();

        $rows = $stats->map(fn ($r) => [$r->source, $r->status, $r->count])->toArray();
        $this->table(['Source', 'Status', 'Count'], $rows);

        $totalNormalized = Event::where('status', Event::STATUS_NORMALIZED)->count();
        $this->components->info("Events normalisés disponibles pour le brief : {$totalNormalized}");

        return self::SUCCESS;
    }
}
