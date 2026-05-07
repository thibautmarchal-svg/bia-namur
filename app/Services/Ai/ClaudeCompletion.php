<?php

namespace App\Services\Ai;

/**
 * Reponse structuree d'un appel Claude (mock ou reel).
 * Immutable, expose les donnees necessaires aux callers + un lien
 * vers la trace ai_runs pour audit.
 */
final class ClaudeCompletion
{
    public function __construct(
        public readonly string $text,
        public readonly string $model,
        public readonly int $inputTokens,
        public readonly int $outputTokens,
        public readonly string $stopReason,
        public readonly bool $isMock = false,
        public readonly ?int $aiRunId = null,
    ) {}

    /** Retourne une copie avec l'id ai_runs renseigne (le service le pose apres logging). */
    public function withAiRunId(int $aiRunId): self
    {
        return new self(
            text: $this->text,
            model: $this->model,
            inputTokens: $this->inputTokens,
            outputTokens: $this->outputTokens,
            stopReason: $this->stopReason,
            isMock: $this->isMock,
            aiRunId: $aiRunId,
        );
    }

    /** Decode le texte comme JSON (pour brief_v1, moderation_v1). */
    public function toJson(): array
    {
        $decoded = json_decode($this->text, true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($decoded)) {
            throw new \RuntimeException('Reponse Claude n\'est pas un JSON object.');
        }

        return $decoded;
    }
}
