<?php

namespace App\Console\Commands;

use App\Services\Ai\ClaudeApiService;
use Illuminate\Console\Command;

/**
 * Test rapide de la connexion API Anthropic.
 *
 * Force mock_mode=false le temps de l'appel et envoie un prompt minimal
 * (5-10 tokens d'output max) pour valider :
 *  - cle ANTHROPIC_API_KEY presente et valide
 *  - reseau OK
 *  - parsing reponse SDK fonctionne
 *  - logging ai_runs ecrit
 *
 * Usage :
 *   php artisan bia:claude:smoke-test
 *   php artisan bia:claude:smoke-test --model=claude-haiku-4-5
 */
class ClaudeSmokeTestCommand extends Command
{
    protected $signature = 'bia:claude:smoke-test
        {--model= : modele a tester (defaut : config bia.ai.models.default)}';

    protected $description = 'Smoke test API Anthropic — 1 appel reel rapide pour valider la connexion. Coute ~$0.001.';

    public function handle(): int
    {
        $apiKey = (string) config('services.anthropic.key', env('ANTHROPIC_API_KEY', ''));
        if ($apiKey === '') {
            $this->components->error('ANTHROPIC_API_KEY manquante dans .env. Pas d\'appel possible.');

            return self::FAILURE;
        }

        $model = $this->option('model') ?: config('bia.ai.models.default');

        $this->components->info("Smoke test Anthropic API — model: {$model}");
        $this->components->info('Cle: '.substr($apiKey, 0, 10).'…'.substr($apiKey, -4).' ('.strlen($apiKey).' chars)');
        $this->newLine();

        // Force mock_mode=false meme si l'env le force a true
        config()->set('bia.ai.mock_mode', false);

        // Prompt minimal : on veut juste verifier la connexion + parsing.
        // On utilise un prompt versionne reel (moderation_v1) car il a un
        // schema JSON simple et un max_tokens bas → peu de couts.
        config()->set('bia.prompts.smoke_test_v1', [
            'system' => 'Tu reponds UNIQUEMENT avec le mot exact "pong", rien d\'autre. Pas de ponctuation, pas de saut de ligne.',
            'temperature' => 0.0,
            'max_tokens' => 10,
        ]);

        $service = new ClaudeApiService(apiKey: $apiKey, mockMode: false);

        try {
            $startedAt = microtime(true);
            $completion = $service->complete('smoke_test_v1', 'ping');
            $duration = (int) ((microtime(true) - $startedAt) * 1000);

            $this->components->info('SUCCESS — appel reel reussi');
            $this->components->bulletList([
                "Reponse texte : « {$completion->text} »",
                "Modele utilise : {$completion->model}",
                "Tokens in/out : {$completion->inputTokens}/{$completion->outputTokens}",
                "Stop reason : {$completion->stopReason}",
                "Duration ms : {$duration}",
                "AiRun id : {$completion->aiRunId} (verifie via 'select * from ai_runs where id = {$completion->aiRunId}')",
            ]);

            $this->newLine();
            if (trim(strtolower($completion->text)) !== 'pong') {
                $this->components->warn(
                    "Reponse inattendue : \"{$completion->text}\" — le modele n'a pas suivi l'instruction. ".
                    'C\'est OK pour un smoke test (l\'important est que la connexion marche), mais a noter pour les prompts reels.',
                );
            } else {
                $this->components->info('La reponse correspond a l\'attendu (pong).');
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->components->error('Echec smoke test : '.$e->getMessage());
            $this->components->bulletList([
                'Exception class : '.get_class($e),
                'Message : '.$e->getMessage(),
            ]);

            $this->newLine();
            $this->line('<fg=yellow>Debug rapide :</>');
            $this->line('  - cle valide ?           '.(strlen($apiKey) >= 30 ? 'longueur OK' : 'TROP COURTE'));
            $this->line('  - prefixe sk-ant- ?      '.(str_starts_with($apiKey, 'sk-ant-') ? 'oui' : 'NON (probable cle de test)'));
            $this->line('  - mock_mode actif ?      '.(config('bia.ai.mock_mode') ? 'OUI (incoherent)' : 'non'));
            $this->line('  - logs ai_runs           : verifie le dernier enregistrement');

            return self::FAILURE;
        }
    }
}
