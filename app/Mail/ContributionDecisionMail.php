<?php

namespace App\Mail;

use App\Models\Contribution;
use App\Models\Place;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Email transactionnel envoye au contributeur quand sa contribution
 * a ete moderee. 3 etats geres : approved (lieu cree + en ligne ou
 * en brouillon), needs_changes (note du moderateur), rejected.
 *
 * Le destinataire est resolu dans le constructeur via :
 *   1. contribution.user.email si user_id rempli
 *   2. payload.contributor_email si contribution anonyme
 *   3. null → l'email n'est tout simplement pas envoye
 */
class ContributionDecisionMail extends Mailable
{
    use Queueable, SerializesModels;

    public const DECISION_APPROVED = 'approved';

    public const DECISION_NEEDS_CHANGES = 'needs_changes';

    public const DECISION_REJECTED = 'rejected';

    public function __construct(
        public readonly Contribution $contribution,
        public readonly string $decision,
        public readonly ?Place $place = null,
        public readonly ?string $reviewerNote = null,
    ) {
        if (! in_array($decision, [self::DECISION_APPROVED, self::DECISION_NEEDS_CHANGES, self::DECISION_REJECTED], true)) {
            throw new \InvalidArgumentException("Decision invalide : {$decision}");
        }
    }

    public function envelope(): Envelope
    {
        $subject = match ($this->decision) {
            self::DECISION_APPROVED => 'Ta contribution est en ligne',
            self::DECISION_NEEDS_CHANGES => 'Une petite précision sur ta contribution',
            self::DECISION_REJECTED => 'On revient vers toi à propos de ta contribution',
        };

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        $payload = $this->contribution->payload ?? [];

        return new Content(
            view: 'emails.contribution-decision',
            with: [
                'decision' => $this->decision,
                'contributorName' => $this->resolveContributorName(),
                'placeName' => $payload['name'] ?? 'ta suggestion',
                'place' => $this->place,
                'placeUrl' => $this->place && $this->place->status === Place::STATUS_PUBLISHED
                    ? url('/lieu/' . $this->place->slug)
                    : null,
                'reviewerNote' => $this->reviewerNote,
            ],
        );
    }

    /** Nom à afficher dans le bonjour. */
    private function resolveContributorName(): string
    {
        if ($this->contribution->user) {
            return $this->contribution->user->name;
        }

        $payload = $this->contribution->payload ?? [];

        return $payload['contributor_name'] ?? 'toi';
    }

    /**
     * Resout l'email du destinataire (user.email ou payload.contributor_email).
     * Retourne null si la contribution etait anonyme sans email.
     */
    public static function recipientFor(Contribution $contribution): ?string
    {
        if ($contribution->user) {
            return $contribution->user->email;
        }

        $payload = $contribution->payload ?? [];

        return $payload['contributor_email'] ?? null;
    }
}
