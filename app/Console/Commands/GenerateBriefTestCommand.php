<?php

namespace App\Console\Commands;

use App\Jobs\GenerateBriefJob;
use App\Models\AiRun;
use App\Models\City;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Lance le pipeline GenerateBriefJob en local pour tester sans queue.
 * Le job va fetch namur.be RSS + appeler Claude (ou la fixture mock).
 *
 * Exemples :
 *   php artisan bia:brief:generate-test
 *   php artisan bia:brief:generate-test --week=20
 *   php artisan bia:brief:generate-test --city=namur --year=2026 --week=22
 *   php artisan bia:brief:generate-test --model=claude-haiku-4-5
 */
class GenerateBriefTestCommand extends Command
{
    protected $signature = 'bia:brief:generate-test
        {--city=namur : slug de la ville}
        {--year= : annee (defaut : annee courante)}
        {--week= : numero de semaine ISO (defaut : semaine courante)}
        {--model= : override le modele (ex: claude-haiku-4-5 si Sonnet sature)}';

    protected $description = 'Genere un brief hebdo en local (mode mock par defaut). À l\'aise.';

    public function handle(): int
    {
        $citySlug = $this->option('city');
        $year = (int) ($this->option('year') ?: now()->year);
        $week = (int) ($this->option('week') ?: now()->isoWeek());

        $this->components->info("Generation brief : {$citySlug} — {$year}-W{$week}");

        $city = City::where('slug', $citySlug)->first();
        if (! $city) {
            $this->components->error("Ville inconnue : {$citySlug}. Lance 'php artisan db:seed --class=CitySeeder'.");

            return self::FAILURE;
        }

        $this->components->info('Mode mock : ' . (config('bia.ai.mock_mode') ? 'OUI (fixtures)' : 'NON (vrai SDK)'));

        $modelOverride = $this->option('model');
        if ($modelOverride) {
            $this->components->info("Modele override : {$modelOverride}");
        }

        $job = new GenerateBriefJob(
            citySlug: $citySlug,
            year: $year,
            weekNumber: $week,
            modelOverride: $modelOverride,
        );

        try {
            $brief = app()->call([$job, 'handle']);
        } catch (\Throwable $e) {
            $this->components->error('Echec : ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->components->info("Brief cree : id={$brief->id}, slug={$brief->slug}, status={$brief->status}");
        $this->components->bulletList([
            "Titre : {$brief->title}",
            'Intro : ' . Str::limit($brief->intro_text ?? '', 100),
            'Items : ' . $brief->items()->count(),
        ]);

        $this->newLine();
        $this->line('<fg=yellow>--- Apercu items ---</>');
        foreach ($brief->items()->orderBy('position')->get() as $item) {
            $this->line("  <fg=cyan>{$item->position}.</> " . Str::limit($item->ai_text, 120));
        }

        $this->newLine();
        $this->components->info('Verifie la trace dans la table ai_runs :');
        $aiRun = AiRun::latest('id')->first();
        if ($aiRun) {
            $this->components->bulletList([
                "type={$aiRun->type}",
                "model={$aiRun->model_used}",
                "tokens in/out={$aiRun->input_tokens}/{$aiRun->output_tokens}",
                "cost_usd={$aiRun->cost_usd}",
                "duration_ms={$aiRun->duration_ms}",
                "status={$aiRun->status}",
            ]);
        }

        return self::SUCCESS;
    }
}
