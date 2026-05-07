<?php

namespace App\Jobs;

use App\Models\Contribution;
use App\Services\Ai\ClaudeApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Modère une contribution utilisateur via Claude.
 *
 * Flux (cf. brief §7.3) :
 *   1. Construit un user_message a partir du payload de la contribution
 *      (sans PII : email/nom user caviardes par le caller)
 *   2. Appelle ClaudeApiService::complete('moderation_v1') qui retourne
 *      un JSON {score, verdict, reasoning}
 *   3. Routage selon score :
 *        score >= config('bia.ai.min_moderation_score_auto_approve') (75)
 *          → status = auto_approved (publication immediate)
 *        score >= config('bia.ai.min_moderation_score_manual_review') (40)
 *          → status = manual_review (file admin)
 *        score < 40
 *          → status = rejected (feedback poli au contributeur)
 *
 * En S1/S2 : mode mock — la fixture moderation_mock_v1.json donne
 * score=78 verdict=approve sur tout. Le routage reel teste seulement
 * en S3 quand on activera Claude.
 */
class ModerateContributionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 60, 180];

    public function __construct(
        public readonly int $contributionId,
    ) {}

    public function handle(ClaudeApiService $claude): Contribution
    {
        $contribution = Contribution::with('user')->findOrFail($this->contributionId);

        // Sanitize : aucune PII envoyée à Claude. Le payload de la contribution
        // contient deja uniquement les champs metiers (nom, description, adresse).
        $userMessage = $this->buildUserMessage($contribution);

        Log::channel('moderation')->info('contribution.moderation.started', [
            'contribution_id' => $contribution->id,
            'type' => $contribution->type,
        ]);

        $completion = $claude->complete(
            promptKey: 'moderation_v1',
            userMessage: $userMessage,
            logContext: ['related_type' => Contribution::class, 'related_id' => $contribution->id],
        );

        $payload = $completion->toJson();
        $score = (int) ($payload['score'] ?? 0);
        $reasoning = $payload['reasoning'] ?? null;

        $minAutoApprove = (int) config('bia.ai.min_moderation_score_auto_approve', 75);
        $minManualReview = (int) config('bia.ai.min_moderation_score_manual_review', 40);

        $newStatus = match (true) {
            $score >= $minAutoApprove => Contribution::STATUS_AUTO_APPROVED,
            $score >= $minManualReview => Contribution::STATUS_MANUAL_REVIEW,
            default => Contribution::STATUS_REJECTED,
        };

        $contribution->update([
            'ai_score' => $score,
            'ai_reasoning' => $reasoning,
            'ai_model' => $completion->model,
            'ai_prompt_version' => 'moderation_v1',
            'status' => $newStatus,
        ]);

        Log::channel('moderation')->info('contribution.moderation.complete', [
            'contribution_id' => $contribution->id,
            'score' => $score,
            'verdict' => $newStatus,
            'is_mock' => $completion->isMock,
        ]);

        return $contribution;
    }

    /**
     * Format pour Claude : type + payload sans PII. Les champs sensibles
     * (email, ip) sont caviardes — Claude n'a pas a les voir.
     */
    protected function buildUserMessage(Contribution $contribution): string
    {
        $payload = $contribution->payload ?? [];

        // Whitelist explicite des champs metier — aucun champ PII (email, nom user, ip).
        $cleanPayload = [
            'type_contribution' => $contribution->type,
            'place_name' => $payload['name'] ?? null,
            'place_type' => $payload['type'] ?? null,
            'description' => $payload['description'] ?? null,
            'address' => $payload['address'] ?? null,
            'neighborhood' => $payload['neighborhood'] ?? null,
            'tags' => $payload['tags'] ?? [],
            'website' => $payload['website'] ?? null,
        ];

        return json_encode($cleanPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
