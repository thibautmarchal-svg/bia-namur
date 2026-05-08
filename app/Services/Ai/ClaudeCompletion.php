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

    /**
     * Decode le texte comme JSON (pour brief_v1, moderation_v1).
     *
     * Tolerant : si la reponse est enrobee de markdown ```json ... ```
     * (Haiku le fait souvent, Sonnet rarement, malgre la consigne du
     * prompt), on extrait le 1er objet/array JSON detecte. Si vraiment
     * rien de parsable, on throw avec un extrait de la reponse pour debug.
     */
    public function toJson(): array
    {
        $text = $this->stripJsonWrapping($this->text);

        try {
            $decoded = json_decode($text, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new \RuntimeException(
                'Reponse Claude pas du JSON valide : '.$e->getMessage().
                ' — debut : '.\Illuminate\Support\Str::limit($this->text, 200),
                0,
                $e,
            );
        }

        if (! is_array($decoded)) {
            throw new \RuntimeException('Reponse Claude n\'est pas un JSON object.');
        }

        return $decoded;
    }

    /**
     * Retire les fences markdown ```json ... ``` ou ``` ... ``` et tout
     * texte avant/apres le 1er { ou [ trouve. Heuristique simple mais
     * robuste contre les modeles qui enrobent leur sortie.
     */
    private function stripJsonWrapping(string $raw): string
    {
        $text = trim($raw);

        // Fences markdown : ```json ... ``` ou ``` ... ```
        if (preg_match('/```(?:json|javascript)?\s*(.+?)\s*```/s', $text, $m)) {
            $text = trim($m[1]);
        }

        // Si du texte trainait avant/apres le bloc, on extrait depuis
        // le 1er { ou [ jusqu'au dernier } ou ] correspondant.
        $candidates = [];
        $bracePos = strpos($text, '{');
        $bracketPos = strpos($text, '[');
        if ($bracePos !== false) {
            $candidates[] = $bracePos;
        }
        if ($bracketPos !== false) {
            $candidates[] = $bracketPos;
        }

        if ($candidates === []) {
            return $text;
        }

        $firstOpen = min($candidates);
        $lastBrace = strrpos($text, '}');
        $lastBracket = strrpos($text, ']');
        $lastClose = max(
            $lastBrace === false ? -1 : $lastBrace,
            $lastBracket === false ? -1 : $lastBracket,
        );

        if ($lastClose > $firstOpen) {
            $text = substr($text, $firstOpen, $lastClose - $firstOpen + 1);
        }

        return $text;
    }
}
