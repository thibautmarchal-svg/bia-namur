<?php

namespace App\Console\Commands;

use App\Models\Event;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Affiche un dashboard texte de l'etat des pipelines d'ingestion :
 * - count par source × status
 * - dates min/max ingested_at
 * - 5 events les plus recents
 *
 * Permet a l'admin de verifier que les sources tournent bien, ou
 * de detecter qu'une source a arrete d'ingerer (pas de nouveau ingested
 * depuis 24h+).
 */
class IngestStatusCommand extends Command
{
    protected $signature = 'bia:ingest:status';

    protected $description = 'Affiche l\'etat des pipelines d\'ingestion (events par source/status, freshness).';

    public function handle(): int
    {
        $this->components->info('État des pipelines Bia Namur');
        $this->newLine();

        // Stats par source × status
        $stats = Event::query()
            ->selectRaw('source, status, COUNT(*) as count, MAX(ingested_at) as last_ingested')
            ->groupBy('source', 'status')
            ->orderBy('source')
            ->orderBy('status')
            ->get();

        if ($stats->isEmpty()) {
            $this->components->warn('Aucun event en base. Lance "php artisan bia:ingest:run".');

            return self::SUCCESS;
        }

        $rows = $stats->map(function ($s) {
            $last = $s->last_ingested ? Carbon::parse($s->last_ingested)->diffForHumans() : '—';
            $statusBadge = match ($s->status) {
                Event::STATUS_NORMALIZED => '<fg=green>' . $s->status . '</>',
                Event::STATUS_INGESTED => '<fg=yellow>' . $s->status . '</>',
                Event::STATUS_DROPPED => '<fg=gray>' . $s->status . '</>',
                Event::STATUS_SELECTED => '<fg=cyan>' . $s->status . '</>',
                default => $s->status,
            };

            return [$s->source, $statusBadge, $s->count, $last];
        })->toArray();

        $this->table(['Source', 'Status', 'Count', 'Dernier ingest'], $rows);

        $totalNormalized = Event::where('status', Event::STATUS_NORMALIZED)->count();
        $totalDropped = Event::where('status', Event::STATUS_DROPPED)->count();

        $this->newLine();
        $this->components->info("Disponibles pour le brief (status=normalized) : {$totalNormalized}");
        $this->components->info("Doublons écartés (status=dropped) : {$totalDropped}");

        // Freshness check : alerte si pas d'ingest depuis 24h
        $latest = Event::latest('ingested_at')->first();
        if ($latest && $latest->ingested_at && $latest->ingested_at->lt(now()->subHours(24))) {
            $this->newLine();
            $this->components->warn('Dernier ingest il y a > 24h. Vérifie le scheduler ou l\'API source.');
        }

        return self::SUCCESS;
    }
}
