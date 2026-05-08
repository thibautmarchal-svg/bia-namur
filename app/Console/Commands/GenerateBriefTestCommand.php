<?php

namespace App\Console\Commands;

use App\Jobs\GenerateBriefJob;
use App\Models\AiRun;
use App\Models\Brief;
use App\Models\City;
use App\Models\Event;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Lance le pipeline GenerateBriefJob en local pour tester sans queue.
 * Si --seed-events, cree quelques events factices namurois pour la
 * semaine ciblee (utile en S1 ou la table events est vide).
 *
 * Exemples :
 *   php artisan bia:brief:generate-test
 *   php artisan bia:brief:generate-test --week=20 --seed-events
 *   php artisan bia:brief:generate-test --city=namur --year=2026 --week=22
 */
class GenerateBriefTestCommand extends Command
{
    protected $signature = 'bia:brief:generate-test
        {--city=namur : slug de la ville}
        {--year= : annee (defaut : annee courante)}
        {--week= : numero de semaine ISO (defaut : semaine courante)}
        {--model= : override le modele (ex: claude-haiku-4-5 si Sonnet sature)}
        {--seed-events : injecte 8 events factices pour la semaine}';

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

        if ($this->option('seed-events')) {
            $this->components->task('Seeding events factices', fn () => $this->seedFakeEvents($city, $year, $week));
        }

        $eventsCount = Event::where('city_id', $city->id)
            ->whereBetween('starts_at', [
                Carbon::now()->setISODate($year, $week)->startOfWeek(),
                Carbon::now()->setISODate($year, $week)->endOfWeek(),
            ])
            ->count();

        $this->components->info("Events disponibles cette semaine : {$eventsCount}");
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

    /**
     * Cree 8 events namurois factices pour la semaine cible (idempotent
     * via external_id). Usage : tests visuels du pipeline en local.
     */
    protected function seedFakeEvents(City $city, int $year, int $week): void
    {
        $weekStart = Carbon::now()->setISODate($year, $week)->startOfWeek();

        $samples = [
            ['Marché du dimanche au Grognon', 'Le Grognon', '+0 days 8:00', '+0 days 13:00', 'opendata', 'marché'],
            ['Vernissage : carnets de voyage', 'Maison de la Culture Bomel', '+5 days 18:30', '+5 days 21:00', 'rss_mcn', 'expo'],
            ['Concert : Sweet Lord Trio', 'Le Belvédère', '+6 days 21:00', '+6 days 23:30', 'rss_belvedere', 'concert'],
            ['Balade nature : la confluence à pied', 'Pont des Ardennes', '+6 days 10:00', '+6 days 13:00', 'opendata', 'nature'],
            ['Conférence : Saintraint, l\'histoire d\'une rue', 'Bibliothèque communale', '+4 days 19:00', '+4 days 20:30', 'opendata', 'patrimoine'],
            ['Dégustation : asperges blanches du moment', 'Le Bia Bouquet', '+3 days 19:30', '+3 days 22:30', 'manual', 'gastronomie'],
            ['Spectacle jeunesse : Le voyage de Bia', 'Théâtre de Namur', '+2 days 14:00', '+2 days 15:30', 'rss_theatre', 'famille'],
            ['Apéro littéraire : Saint-Loup', 'Église Saint-Loup', '+1 days 18:00', '+1 days 20:00', 'manual', 'culture'],
        ];

        foreach ($samples as $i => [$title, $venue, $startsOffset, $endsOffset, $source, $cat]) {
            $startsAt = (clone $weekStart)->modify($startsOffset);
            $endsAt = (clone $weekStart)->modify($endsOffset);

            Event::updateOrCreate(
                [
                    'source' => $source,
                    'external_id' => "fake-{$year}-W{$week}-{$i}",
                ],
                [
                    'city_id' => $city->id,
                    'title' => $title,
                    'description' => "Evenement factice pour test pipeline brief — {$title}.",
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'venue_name' => $venue,
                    'category' => [$cat],
                    'price_info' => $i % 2 === 0 ? 'Gratuit' : '12€',
                    'status' => Event::STATUS_NORMALIZED,
                    'ingested_at' => now(),
                ],
            );
        }
    }
}
