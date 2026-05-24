<?php

namespace App\Console\Commands;

use App\Models\Brief;
use App\Models\City;
use App\Services\Telegram\TelegramNotifier;
use Illuminate\Console\Command;

/**
 * Verifie qu'un brief existe bien pour la semaine ISO en cours, et
 * alerte l'admin sur Telegram si non.
 *
 * Lance automatiquement par le scheduler chaque vendredi a 14h15 (15 min
 * apres l'horaire prevu de GenerateBriefJob). Garantit qu'un brief non
 * genere ne passe pas inapercu (ce qui s'est passe le 22 mai : 3 retries
 * Anthropic auth fail, aucune notification).
 *
 * Le brief est considere "OK" si on trouve une ligne briefs avec :
 *  - city_id correspondant a la city (defaut 'namur')
 *  - year/week_number = semaine ISO en cours
 *  - status != archived (donc draft_ai, pending_review ou published)
 *
 * Note : on accepte aussi soft-deleted volontairement → si un brief a
 * ete genere puis soft-deleted exprès par l'admin (rejet), on assume
 * que le rejet est intentionnel, donc pas d'alerte.
 */
class CheckWeeklyBriefCommand extends Command
{
    protected $signature = 'bia:brief:check-weekly
        {--city=namur : slug de la ville a verifier}';

    protected $description = 'Verifie qu\'un brief existe pour la semaine ISO courante. Alerte Telegram si absent.';

    public function handle(TelegramNotifier $telegram): int
    {
        $citySlug = $this->option('city');
        $city = City::where('slug', $citySlug)->first();

        if (! $city) {
            $this->components->error("Ville '{$citySlug}' introuvable.");

            return self::FAILURE;
        }

        $now = now('Europe/Brussels');
        $year = $now->year;
        $week = $now->isoWeek();
        $expectedSlug = sprintf('%d-W%02d', $year, $week);

        $brief = Brief::query()
            ->where('city_id', $city->id)
            ->where('year', $year)
            ->where('week_number', $week)
            ->whereIn('status', [
                Brief::STATUS_DRAFT_AI,
                Brief::STATUS_PENDING_REVIEW,
                Brief::STATUS_PUBLISHED,
            ])
            ->first();

        if ($brief) {
            $this->components->info("Brief {$expectedSlug} trouve (status: {$brief->status}). RAS.");

            return self::SUCCESS;
        }

        $this->components->warn("Brief {$expectedSlug} ABSENT. Alerte Telegram envoyee.");

        $message = "⚠️ *Brief {$expectedSlug} introuvable*\n\n"
            . "Pas de brief en draft_ai/pending_review/published pour la semaine en cours.\n\n"
            . "Causes probables :\n"
            . "• Clé Anthropic invalide → check les ai_runs failed\n"
            . "• Queue worker bloqué → check failed_jobs\n"
            . "• Source RSS namur.be down → check logs\n\n"
            . "Diagnostic complet :\n"
            . url('/_deploy/brief-pipeline-status?secret=BIA_DEPLOY_SECRET');

        $sent = $telegram->sendAdminAlert($message);

        if (! $sent) {
            $this->components->error('Telegram alert FAILED (notifier disabled or token invalid).');
        }

        return self::SUCCESS;    // On ne fail pas la command pour ne pas spammer les retries scheduler
    }
}
