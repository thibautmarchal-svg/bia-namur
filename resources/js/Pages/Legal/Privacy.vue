<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
    updatedAt: { type: String, required: true },
});
</script>

<template>
    <Head>
        <title>Politique de confidentialité — Bia Namur</title>
        <meta name="robots" content="noindex" />
    </Head>

    <AppLayout>
        <article class="container-editorial py-editorial max-w-reading prose-legal">
            <p class="font-sans text-caption uppercase tracking-[0.2em] text-bia-primary mb-4">
                Pages légales
            </p>
            <h1 class="font-serif text-h1 font-medium text-bia-ink mb-3">
                Politique de confidentialité
            </h1>
            <p class="text-caption text-bia-ink-mute italic mb-editorial">
                Dernière mise à jour&nbsp;: {{ updatedAt }}
            </p>

            <p class="font-serif italic text-body text-bia-ink-soft">
                Bia Namur a été conçu avec une exigence simple&nbsp;: collecter le minimum
                nécessaire et ne jamais revendre tes données.
            </p>

            <h2>1. Responsable du traitement</h2>
            <p>
                <strong>Thibaut Marchal</strong>, en qualité d'éditeur de Bia Namur, est responsable
                du traitement des données personnelles collectées via le site, au sens du Règlement
                général sur la protection des données (RGPD&nbsp;— UE 2016/679) et de la loi belge
                du 30&nbsp;juillet 2018.
            </p>
            <p>
                Contact RGPD&nbsp;: <a href="mailto:privacy@bianamur.be">privacy@bianamur.be</a>
            </p>

            <h2>2. Données collectées</h2>
            <p>Bia Namur collecte les données suivantes&nbsp;:</p>
            <ul>
                <li><strong>À l'inscription</strong>&nbsp;: adresse email, prénom (optionnel).
                    Aucun mot de passe n'est jamais demandé&nbsp;: l'authentification se fait par lien magique.</li>
                <li><strong>Lors d'une contribution</strong>&nbsp;: contenu de la suggestion (nom de
                    lieu, description, adresse, photo si fournie), prénom et email du contributeur
                    (optionnels), adresse IP et user-agent à des fins anti-spam.</li>
                <li><strong>Pour les utilisateurs connectés</strong>&nbsp;: favoris, préférences de
                    notification, historique de connexion.</li>
                <li><strong>Statistiques anonymes de visite</strong>&nbsp;: via Plausible Analytics
                    (sans cookies, sans tracking individuel).</li>
            </ul>

            <h2>3. Finalités du traitement</h2>
            <ul>
                <li>Permettre l'authentification et la session utilisateur.</li>
                <li>Modérer et publier les contributions.</li>
                <li>Envoyer le brief hebdomadaire et les notifications de proximité (si opt-in).</li>
                <li>Améliorer la pertinence éditoriale du contenu.</li>
                <li>Répondre aux questions adressées par email.</li>
                <li>Détecter et prévenir les abus (spam, scraping).</li>
            </ul>

            <h2>4. Bases légales (article&nbsp;6 RGPD)</h2>
            <ul>
                <li><strong>Consentement</strong>&nbsp;: notifications push, géolocalisation
                    «&nbsp;autour de moi&nbsp;», acceptation des CGU à l'inscription.</li>
                <li><strong>Exécution du contrat</strong>&nbsp;: création et gestion du compte
                    utilisateur, fonctionnalités du service.</li>
                <li><strong>Intérêt légitime</strong>&nbsp;: modération des contributions,
                    détection des abus, statistiques anonymes.</li>
            </ul>

            <h2>5. Durée de conservation</h2>
            <ul>
                <li>Compte utilisateur&nbsp;: aussi longtemps que le compte existe, supprimé
                    sous 30&nbsp;jours après demande d'effacement.</li>
                <li>Liens magiques&nbsp;: 15&nbsp;minutes (expiration), supprimés sous 30&nbsp;jours
                    après usage ou expiration.</li>
                <li>Contributions publiées&nbsp;: durée d'exploitation du site (les contributions
                    intégrées peuvent être anonymisées sur demande, mais pas supprimées si elles
                    sont publiquement intégrées au contenu éditorial).</li>
                <li>Logs techniques&nbsp;: 30&nbsp;jours pour les logs d'erreur et d'activité.</li>
                <li>Statistiques Plausible&nbsp;: agrégées et anonymes, conservées sans limite.</li>
            </ul>

            <h2>6. Destinataires et sous-traitants</h2>
            <p>Tes données sont traitées par&nbsp;:</p>
            <ul>
                <li><strong>L'éditeur</strong> (Thibaut Marchal) pour l'ensemble des fonctionnalités.</li>
                <li><strong>L'hébergeur</strong> (à compléter à la mise en production)&nbsp;: stockage
                    base de données, fichiers, logs. Localisé dans l'Union européenne.</li>
                <li><strong>Cloudflare R2</strong> (UE)&nbsp;: stockage des photos.</li>
                <li><strong>Mailgun ou équivalent</strong> (UE)&nbsp;: envoi des emails transactionnels.</li>
                <li><strong>Sentry</strong> (UE — région Allemagne)&nbsp;: logs d'erreurs applicatives.</li>
                <li><strong>Plausible Analytics</strong> (UE — Allemagne)&nbsp;: statistiques de visite anonymes.</li>
            </ul>

            <h2>7. Transfert hors Union européenne&nbsp;: Anthropic (États-Unis)</h2>
            <p>
                Bia Namur utilise les services d'<strong>Anthropic</strong> (États-Unis) pour assister
                la rédaction du brief hebdomadaire, des stories, et la modération des contributions.
                Les données suivantes <em>peuvent</em> être transmises à Anthropic dans le cadre de
                ces traitements&nbsp;:
            </p>
            <ul>
                <li>Le contenu éditorial public à modérer ou résumer (descriptions de lieux,
                    titres d'événements, contenus déjà publics ou destinés à être publiés).</li>
            </ul>
            <p>
                <strong>Aucune donnée personnelle (email, prénom, adresse IP, identifiant utilisateur)
                n'est jamais transmise à Anthropic.</strong> Le code applicatif assure la sanitization
                automatique des prompts avant envoi&nbsp;: seuls les champs métier publics sont inclus.
            </p>
            <p>
                Le transfert est encadré par les clauses contractuelles types de la Commission
                européenne (Standard Contractual Clauses — SCC) et le Data Processing Addendum (DPA)
                fourni par Anthropic, conformément aux articles&nbsp;46 et suivants du RGPD.
                Plus d'informations&nbsp;:
                <a href="https://www.anthropic.com/legal/dpa" target="_blank" rel="noopener noreferrer">
                    Anthropic Data Processing Addendum
                </a>.
            </p>

            <h2>8. Cookies et traceurs</h2>
            <p>Bia Namur utilise uniquement les cookies suivants, tous strictement nécessaires&nbsp;:</p>
            <ul>
                <li><strong>Session</strong>&nbsp;: cookie de session (login, panier, préférences),
                    expire à la déconnexion ou après 30&nbsp;jours d'inactivité.</li>
                <li><strong>CSRF</strong>&nbsp;: protection contre les attaques cross-site request forgery.</li>
            </ul>
            <p>
                <strong>Aucun cookie de tracking publicitaire, aucun pixel Meta, aucun Google
                Analytics, aucun retargeting.</strong> Les statistiques de visite sont mesurées par
                <a href="https://plausible.io" target="_blank" rel="noopener noreferrer">Plausible Analytics</a>
                qui ne dépose aucun cookie et ne collecte aucune donnée individuelle.
                Aucune bannière de consentement aux cookies n'est donc requise.
            </p>

            <h2>9. Tes droits (articles&nbsp;15 à 22 RGPD)</h2>
            <ul>
                <li><strong>Droit d'accès</strong>&nbsp;: demander une copie de tes données via
                    le bouton «&nbsp;Exporter mes données&nbsp;» dans ton compte ou par email à
                    <a href="mailto:privacy@bianamur.be">privacy@bianamur.be</a>.</li>
                <li><strong>Droit de rectification</strong>&nbsp;: corriger directement tes données
                    dans ton compte, ou nous contacter.</li>
                <li><strong>Droit à l'effacement</strong>&nbsp;: bouton «&nbsp;Supprimer mon compte&nbsp;»
                    dans ton compte. Suppression complète sous 30&nbsp;jours, anonymisation des
                    contributions publiquement intégrées au site.</li>
                <li><strong>Droit à la portabilité</strong>&nbsp;: export en format JSON ouvert.</li>
                <li><strong>Droit d'opposition et de limitation</strong>&nbsp;: désabonnement aux
                    notifications via les préférences de compte ou par email.</li>
                <li><strong>Droit de réclamation</strong>&nbsp;: tu peux saisir l'Autorité de
                    Protection des Données belge (<a href="https://www.autoriteprotectiondonnees.be" target="_blank" rel="noopener noreferrer">autoriteprotectiondonnees.be</a>)
                    si tu estimes que tes droits ne sont pas respectés.</li>
            </ul>

            <h2>10. Sécurité</h2>
            <p>
                Les données personnelles sont stockées sur des serveurs sécurisés. Les emails sont
                hashés (SHA-256) dans les logs. Les liens magiques sont stockés sous forme hashée
                en base de données et expirent après 15 minutes. Les uploads de photos sont
                automatiquement nettoyés de leurs métadonnées EXIF (géolocalisation, modèle d'appareil)
                avant stockage.
            </p>

            <h2>11. Mineurs</h2>
            <p>
                Bia Namur est ouvert à partir de <strong>16&nbsp;ans</strong> (seuil RGPD belge).
                Pour les mineurs en dessous de cet âge, le consentement parental ou du représentant
                légal est requis, à demander via <a href="mailto:privacy@bianamur.be">privacy@bianamur.be</a>.
            </p>

            <h2>12. Mises à jour de cette politique</h2>
            <p>
                Cette politique peut être amenée à évoluer. La date de dernière mise à jour est
                indiquée en haut de cette page. Les utilisateurs inscrits seront informés par email
                des modifications substantielles.
            </p>

            <p class="mt-editorial text-caption text-bia-ink-mute italic">
                Voir aussi&nbsp;:
                <Link href="/mentions-legales" class="underline hover:text-bia-primary">Mentions légales</Link>
                &middot;
                <Link href="/cgu" class="underline hover:text-bia-primary">Conditions générales d'utilisation</Link>
            </p>
        </article>
    </AppLayout>
</template>

<style scoped>
.prose-legal :deep(h2) {
    font-family: theme('fontFamily.serif');
    font-size: 1.5rem;
    font-weight: 500;
    color: theme('colors.bia.ink');
    margin-top: theme('spacing.editorial');
    margin-bottom: theme('spacing.reading');
}
.prose-legal :deep(p) {
    color: theme('colors.bia.ink-soft');
    line-height: 1.8;
    margin-bottom: theme('spacing.reading');
}
.prose-legal :deep(ul) {
    color: theme('colors.bia.ink-soft');
    line-height: 1.8;
    margin-bottom: theme('spacing.reading');
    padding-left: 1.25rem;
    list-style: disc;
}
.prose-legal :deep(li) {
    margin-bottom: 0.4rem;
}
.prose-legal :deep(a) {
    color: theme('colors.bia.primary');
    text-decoration: none;
    text-underline-offset: 4px;
}
.prose-legal :deep(a:hover) {
    color: theme('colors.bia.primary-dk');
    text-decoration: underline;
}
.prose-legal :deep(strong) {
    color: theme('colors.bia.ink');
    font-weight: 600;
}
</style>
