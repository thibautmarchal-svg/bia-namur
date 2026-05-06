<?php

namespace Database\Seeders;

use App\Models\Entitlement;
use Illuminate\Database\Seeder;

class EntitlementSeeder extends Seeder
{
    public function run(): void
    {
        $entitlements = [
            // FREE — capabilities du tier gratuit (utilisable au lancement)
            [
                'code' => 'consult_brief',
                'label' => 'Consulter le brief hebdo',
                'description' => 'Lire le brief de la semaine et les archives.',
                'tier_required' => 'free',
            ],
            [
                'code' => 'browse_places',
                'label' => 'Parcourir la carte sentimentale',
                'description' => 'Voir tous les lieux publiés et leur fiche.',
                'tier_required' => 'free',
            ],
            [
                'code' => 'read_stories',
                'label' => 'Lire les stories',
                'description' => 'Accès complet aux stories patrimoine et wallon.',
                'tier_required' => 'free',
            ],
            [
                'code' => 'limited_favorites',
                'label' => 'Favoris (jusqu\'à 20)',
                'description' => 'Sauvegarder jusqu\'à 20 lieux ou stories favoris.',
                'tier_required' => 'free',
            ],
            [
                'code' => 'submit_contribution',
                'label' => 'Contribuer (3/jour)',
                'description' => 'Suggérer un lieu, photo ou correction (rate limit 3/jour).',
                'tier_required' => 'free',
            ],

            // PLUS — Bia + (M6+)
            [
                'code' => 'unlimited_favorites',
                'label' => 'Favoris illimités',
                'description' => 'Listes thématiques sans limite (« Mes terrasses », « Pour les beaux-parents »…).',
                'tier_required' => 'plus',
            ],
            [
                'code' => 'proximity_notifs',
                'label' => 'Notifications de proximité',
                'description' => 'Alertes quand un événement suivi est à 500 m.',
                'tier_required' => 'plus',
            ],
            [
                'code' => 'personalized_brief',
                'label' => 'Brief personnalisé',
                'description' => 'Filtrage thématique de la curation hebdomadaire.',
                'tier_required' => 'plus',
            ],
            [
                'code' => 'offline_stories',
                'label' => 'Mode hors-ligne enrichi',
                'description' => 'Stories complètes accessibles sans connexion.',
                'tier_required' => 'plus',
            ],
            [
                'code' => 'visit_journal',
                'label' => 'Carnet de visite',
                'description' => 'Journal personnel avec photos et notes par lieu visité.',
                'tier_required' => 'plus',
            ],

            // PATRON — soutien renforcé
            [
                'code' => 'patron_badge',
                'label' => 'Badge Mécène namurois',
                'description' => 'Statut visible « Mécène namurois » sur les contributions publiques.',
                'tier_required' => 'patron',
            ],
        ];

        foreach ($entitlements as $entitlement) {
            Entitlement::updateOrCreate(['code' => $entitlement['code']], $entitlement);
        }
    }
}
