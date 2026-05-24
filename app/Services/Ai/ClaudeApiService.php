<?php

namespace App\Services\Ai;

use Anthropic\Client;
use App\Models\AiRun;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Service unique d'appel a l'API Claude.
 *
 * Comportement controle par config('bia.ai.mock_mode') :
 *  - mock_mode = true : retourne des fixtures depuis tests/Fixtures/claude/.
 *    Aucun appel reseau, aucun cout.
 *  - mock_mode = false : appel reel via le SDK anthropic-ai/sdk avec
 *    retry 3x backoff exponentiel + timeout 60s par defaut.
 *
 * Logging systematique dans ai_runs (input/output tokens, cost USD,
 * duration ms, status, error message). Permet :
 *  - cost tracking mensuel pour respecter le budget Claude
 *  - debug en cas de regression de qualite
 *  - statistiques par type de pipeline (brief vs story vs moderation)
 *
 * Aucun PII envoye a Claude (cf. agent security-namur). Les emails et
 * noms utilisateurs sont caviardes en amont par le caller.
 */
class ClaudeApiService
{
    public function __construct(
        protected ?string $apiKey = null,
        protected bool $mockMode = false,
        protected ?Client $client = null,
    ) {
        $this->apiKey = $apiKey ?? (string) config('services.anthropic.key', env('ANTHROPIC_API_KEY', ''));
        $this->mockMode = $mockMode || (bool) config('bia.ai.mock_mode', false);
    }

    /**
     * Genere une completion via le prompt versionne demande.
     *
     * @param  string  $promptKey  ex: 'brief_v1', 'story_v1', 'moderation_v1'
     * @param  string  $userMessage  contenu utilisateur (events JSON, contexte story, contribution payload)
     * @param  string|null  $modelOverride  override le modele par defaut (utiliser pour Opus sur stories complexes)
     * @param  array<string,mixed>  $logContext  metadonnees pour ai_runs (related_type, related_id, etc.)
     */
    public function complete(
        string $promptKey,
        string $userMessage,
        ?string $modelOverride = null,
        array $logContext = [],
    ): ClaudeCompletion {
        $prompt = config("bia.prompts.{$promptKey}");
        if (! is_array($prompt) || empty($prompt['system'])) {
            throw new RuntimeException("Prompt inconnu : {$promptKey}");
        }

        $model = $modelOverride ?? config('bia.ai.models.default');
        $type = $this->inferType($promptKey);

        $aiRun = AiRun::create([
            'type' => $type,
            'model_used' => $model,
            'prompt_template_version' => $promptKey,
            'status' => AiRun::STATUS_PENDING,
            'related_type' => $logContext['related_type'] ?? null,
            'related_id' => $logContext['related_id'] ?? null,
        ]);

        $startedAt = microtime(true);

        try {
            $completion = $this->mockMode
                ? $this->callMock($promptKey, $userMessage, $model)
                : $this->callApi($prompt, $userMessage, $model);

            $aiRun->update([
                'input_tokens' => $completion->inputTokens,
                'output_tokens' => $completion->outputTokens,
                'cost_usd' => $this->computeCost($model, $completion->inputTokens, $completion->outputTokens),
                'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
                'status' => AiRun::STATUS_SUCCESS,
            ]);

            return $completion->withAiRunId($aiRun->id);
        } catch (Throwable $e) {
            $aiRun->update([
                'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
                'status' => AiRun::STATUS_FAILED,
                'error_message' => Str::limit($e->getMessage(), 500),
            ]);

            Log::channel(config('logging.default'))->error('claude_api.call_failed', [
                'prompt_key' => $promptKey,
                'model' => $model,
                'ai_run_id' => $aiRun->id,
                'error_class' => get_class($e),
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Mode mock : charge la fixture correspondant au prompt.
     * Simule un cout indicatif et un temps de reponse minimal pour
     * que les ai_runs aient des donnees realistes en local.
     */
    protected function callMock(string $promptKey, string $userMessage, string $model): ClaudeCompletion
    {
        $fixturePath = base_path("tests/Fixtures/claude/{$this->fixtureFilename($promptKey)}");

        if (! is_file($fixturePath)) {
            throw new RuntimeException("Fixture introuvable pour {$promptKey} : {$fixturePath}");
        }

        $body = file_get_contents($fixturePath);

        // Simulation indicative pour avoir des stats coherentes en local.
        $inputTokens = (int) ceil(mb_strlen($userMessage) / 3.5);
        $outputTokens = (int) ceil(mb_strlen($body) / 3.5);

        usleep(50_000); // 50ms simules pour ne pas avoir duration_ms=0

        return new ClaudeCompletion(
            text: $body,
            model: $model,
            inputTokens: $inputTokens,
            outputTokens: $outputTokens,
            stopReason: 'mock',
            isMock: true,
        );
    }

    /**
     * Appel reel a l'API Anthropic via HTTP raw (Laravel Http facade).
     *
     * Pourquoi pas le SDK officiel anthropic-ai/sdk ?
     * → En prod le SDK genere systematiquement "x-api-key header is required"
     *   (401) alors que la meme cle marche en HTTP raw (cf. healthcheck).
     *   Bug subtil du SDK qu'on n'a pas reussi a reproduire en local. On
     *   bypass donc le SDK et on utilise l'API Anthropic REST directement.
     *
     * - retry implemente a la main pour les 5xx et timeouts
     *   (les 4xx auth/rate-limit ne se retrytent pas)
     * - timeout = config('bia.ai.timeout_seconds', 60)
     */
    protected function callApi(array $prompt, string $userMessage, string $model): ClaudeCompletion
    {
        if (empty($this->apiKey)) {
            throw new RuntimeException('ANTHROPIC_API_KEY manquante. Active le mock_mode ou configure la cle.');
        }

        $payload = [
            'model' => $model,
            'max_tokens' => (int) ($prompt['max_tokens'] ?? 2000),
            'temperature' => (float) ($prompt['temperature'] ?? 0.5),
            'system' => $prompt['system'],
            'messages' => [
                ['role' => 'user', 'content' => $userMessage],
            ],
        ];

        $maxRetries = (int) config('bia.ai.max_retries', 3);
        $timeout = (int) config('bia.ai.timeout_seconds', 60);

        $attempt = 0;
        while (true) {
            $attempt++;
            try {
                $response = Http::withHeaders([
                    'x-api-key' => trim($this->apiKey),
                    'anthropic-version' => '2023-06-01',
                    'content-type' => 'application/json',
                ])
                    ->timeout($timeout)
                    ->post('https://api.anthropic.com/v1/messages', $payload);
            } catch (Throwable $e) {
                // Erreur transport (timeout, DNS, etc.) — on retry si possible
                if ($attempt < $maxRetries) {
                    usleep(500_000 * (2 ** ($attempt - 1)));    // backoff 0.5s, 1s, 2s...

                    continue;
                }
                throw new RuntimeException('Anthropic API transport error: ' . $e->getMessage(), 0, $e);
            }

            // 200 → succes
            if ($response->successful()) {
                return $this->parseResponse($response->json(), $model);
            }

            // 5xx → retry
            if ($response->serverError() && $attempt < $maxRetries) {
                usleep(500_000 * (2 ** ($attempt - 1)));

                continue;
            }

            // 4xx (auth, rate-limit, bad request) → fail immediat avec message clair
            throw new RuntimeException(sprintf(
                'Anthropic API error: %d %s — %s',
                $response->status(),
                $response->reason(),
                Str::limit($response->body(), 500),
            ));
        }
    }

    /**
     * Parse la reponse JSON d'Anthropic en ClaudeCompletion.
     */
    protected function parseResponse(array $body, string $model): ClaudeCompletion
    {
        $text = '';
        foreach ($body['content'] ?? [] as $block) {
            if (($block['type'] ?? null) === 'text') {
                $text = (string) ($block['text'] ?? '');
                break;
            }
        }

        if ($text === '') {
            throw new RuntimeException('Aucun bloc text dans la reponse Anthropic.');
        }

        return new ClaudeCompletion(
            text: $text,
            model: (string) ($body['model'] ?? $model),
            inputTokens: (int) ($body['usage']['input_tokens'] ?? 0),
            outputTokens: (int) ($body['usage']['output_tokens'] ?? 0),
            stopReason: (string) ($body['stop_reason'] ?? 'unknown'),
            isMock: false,
        );
    }

    /**
     * Calcule le cout USD d'un appel selon les prix indicatifs config/bia.php.
     * Si le modele n'est pas dans la table de prix (modele inconnu), retourne 0.
     */
    protected function computeCost(string $model, int $inputTokens, int $outputTokens): float
    {
        $pricing = config("bia.pricing.{$model}");
        if (! is_array($pricing)) {
            return 0.0;
        }

        return round(
            ($inputTokens / 1_000_000) * $pricing['input']
            + ($outputTokens / 1_000_000) * $pricing['output'],
            6,
        );
    }

    protected function inferType(string $promptKey): string
    {
        return match (true) {
            str_starts_with($promptKey, 'brief_') => AiRun::TYPE_BRIEF_WEEKLY,
            str_starts_with($promptKey, 'story_') => AiRun::TYPE_STORY_GENERATION,
            str_starts_with($promptKey, 'moderation_') => AiRun::TYPE_CONTRIBUTION_MODERATION,
            default => 'unknown',
        };
    }

    protected function fixtureFilename(string $promptKey): string
    {
        return match (true) {
            str_starts_with($promptKey, 'brief_') => 'brief_mock_v1.json',
            str_starts_with($promptKey, 'story_') => 'story_mock_v1.md',
            str_starts_with($promptKey, 'moderation_') => 'moderation_mock_v1.json',
            default => "{$promptKey}.txt",
        };
    }
}
