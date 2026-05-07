<?php

namespace App\Services\Ingestion;

/**
 * Categorise un event Bia par mots-cles, sans IA.
 *
 * Premier match dans la table config('bia.categorization_rules') gagne.
 * Si aucun match, retourne null — Claude tranchera en S2 sur les cas
 * ambigus (pipeline event_categorization).
 *
 * Strategie volontairement simple : on accepte des faux negatifs (events
 * non categorises) plutot que des faux positifs (mauvaise categorie).
 */
class EventCategorizationService
{
    public function categorize(?string $title, ?string $description = null, ?string $sourceCategory = null): ?string
    {
        $haystack = mb_strtolower(implode(' ', array_filter([$title, $description, $sourceCategory])));
        if ($haystack === '') {
            return null;
        }

        $rules = config('bia.categorization_rules', []);

        foreach ($rules as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($haystack, mb_strtolower($keyword))) {
                    return $category;
                }
            }
        }

        return null;
    }
}
