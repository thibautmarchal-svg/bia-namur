<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
    updatedAt: { type: String, required: true },
});
</script>

<template>
    <Head>
        <title>Conditions générales d'utilisation — Bia Namur</title>
        <meta name="robots" content="noindex" />
    </Head>

    <AppLayout>
        <article class="container-editorial py-editorial max-w-reading prose-legal">
            <p class="font-sans text-caption uppercase tracking-[0.2em] text-bia-primary mb-4">
                Pages légales
            </p>
            <h1 class="font-serif text-h1 font-medium text-bia-ink mb-3">
                Conditions générales d'utilisation
            </h1>
            <p class="text-caption text-bia-ink-mute italic mb-editorial">
                Dernière mise à jour&nbsp;: {{ updatedAt }}
            </p>

            <h2>1. Objet</h2>
            <p>
                Les présentes Conditions générales d'utilisation (ci-après «&nbsp;CGU&nbsp;»)
                régissent l'accès au site Bia Namur et l'usage de ses fonctionnalités.
                L'utilisation du site implique l'acceptation pleine et entière des présentes CGU.
                Si tu n'es pas d'accord avec ces conditions, tu n'es pas autorisé(e) à utiliser le site.
            </p>

            <h2>2. Service proposé</h2>
            <p>
                Bia Namur est un service éditorial gratuit proposant&nbsp;:
            </p>
            <ul>
                <li>un brief hebdomadaire de sélections culturelles namuroises&nbsp;;</li>
                <li>une carte sentimentale de lieux de Namur et environs&nbsp;;</li>
                <li>des stories de patrimoine, traditions et wallon namurois&nbsp;;</li>
                <li>un formulaire de contribution permettant aux utilisateurs de suggérer des lieux.</li>
            </ul>

            <h2>3. Compte utilisateur</h2>
            <p>
                L'inscription et la connexion se font via un système de lien magique envoyé par email
                (pas de mot de passe à retenir). L'utilisateur s'engage à fournir une adresse email
                valide et à ne pas usurper l'identité d'autrui. L'utilisateur est responsable de la
                confidentialité de son lien magique et de toute activité réalisée depuis son compte.
            </p>
            <p>
                <strong>Âge minimum&nbsp;: 16&nbsp;ans</strong> (seuil RGPD belge). Pour les mineurs
                de moins de 16&nbsp;ans, le consentement des parents ou du représentant légal est
                requis pour la création d'un compte.
            </p>

            <h2>4. Contributions</h2>
            <p>
                En soumettant une contribution (suggestion de lieu, photo, correction, story),
                l'utilisateur déclare&nbsp;:
            </p>
            <ul>
                <li>en être l'auteur ou disposer des droits nécessaires&nbsp;;</li>
                <li>que le contenu ne porte atteinte à aucun droit de tiers (droit à l'image,
                    propriété intellectuelle, vie privée)&nbsp;;</li>
                <li>que les informations communiquées sont sincères et exactes.</li>
            </ul>
            <p>
                L'utilisateur cède à Bia Namur, à titre gratuit et non exclusif, le droit de
                reproduire, adapter et publier sa contribution sur le site, dans les briefs, dans
                d'éventuelles publications dérivées (édition papier annuelle, newsletters), et ce
                pour la durée des droits d'auteur. Bia Namur peut éditer, raccourcir ou reformuler
                les contributions pour les harmoniser avec le ton éditorial.
            </p>
            <p>
                Bia Namur s'engage à attribuer la contribution à son auteur lorsque c'est pertinent
                (sauf si l'auteur souhaite rester anonyme).
            </p>

            <h2>5. Modération</h2>
            <p>
                Toutes les contributions font l'objet d'une modération. Bia Namur se réserve le droit
                de refuser, modifier, anonymiser, retarder ou supprimer toute contribution qui&nbsp;:
            </p>
            <ul>
                <li>contiendrait des propos illicites, diffamatoires, racistes, sexistes ou haineux&nbsp;;</li>
                <li>constituerait une publicité déguisée ou un placement de produit non transparent&nbsp;;</li>
                <li>porterait atteinte à la vie privée de tiers&nbsp;;</li>
                <li>ne respecterait pas le ton éditorial du site&nbsp;;</li>
                <li>serait manifestement faux ou trompeur.</li>
            </ul>

            <h2>6. Propriété intellectuelle</h2>
            <p>
                Les contenus éditoriaux publiés sur Bia Namur (briefs, stories, descriptions rédigées
                par l'éditeur) sont protégés par le droit d'auteur. Toute reproduction, totale ou
                partielle, sans autorisation écrite préalable, est interdite et constitue une
                contrefaçon.
            </p>
            <p>
                <strong>Clause anti-scraping et anti-réutilisation commerciale.</strong>
                L'extraction automatisée de données (scraping, crawling, moissonnage), la
                réutilisation à des fins commerciales sans accord préalable, ainsi que toute tentative
                de reconstitution d'une base de données substantielle à partir des contenus du site
                sont expressément interdites. Cette interdiction couvre notamment&nbsp;:
            </p>
            <ul>
                <li>l'extraction des fiches lieux pour publication sur un site tiers&nbsp;;</li>
                <li>la réutilisation des stories patrimoniales sans citation explicite&nbsp;;</li>
                <li>le scraping des briefs hebdomadaires&nbsp;;</li>
                <li>la copie de l'identité visuelle ou de l'angle éditorial.</li>
            </ul>
            <p>
                Les robots d'indexation des moteurs de recherche grand public (Google, Bing, DuckDuckGo)
                sont autorisés. Les robots commerciaux (SemrushBot, AhrefsBot, etc.) sont interdits via
                <code>robots.txt</code>.
            </p>

            <h2>7. Limitations de responsabilité</h2>
            <p>
                Les informations publiées (horaires, adresses, événements) sont communiquées à titre
                indicatif. Vérifiez auprès de l'organisateur ou du lieu avant tout déplacement.
                Bia Namur ne saurait être tenu responsable&nbsp;:
            </p>
            <ul>
                <li>d'une information obsolète, incomplète ou erronée provenant de sources externes
                    (OpenData Namur, RSS culturels)&nbsp;;</li>
                <li>d'un changement d'horaires ou d'une annulation par l'organisateur&nbsp;;</li>
                <li>d'une interruption temporaire du service pour maintenance ou cause de force majeure&nbsp;;</li>
                <li>de l'usage qui serait fait par un tiers des informations publiées.</li>
            </ul>

            <h2>8. Modèle économique</h2>
            <p>
                Bia Namur est gratuit pour la consultation du brief hebdomadaire, de la carte
                sentimentale et des stories. À l'avenir, l'éditeur pourra proposer&nbsp;:
            </p>
            <ul>
                <li>un abonnement optionnel <strong>Bia +</strong> donnant accès à des fonctionnalités
                    enrichies (favoris illimités, notifications de proximité, brief personnalisé)&nbsp;;</li>
                <li>des encarts éditorialisés clairement identifiés en partenariat avec des
                    commerçants locaux.</li>
            </ul>
            <p>
                Aucune publicité tierce, aucun pixel de tracking publicitaire, aucune revente de
                données utilisateur ne seront mis en place.
            </p>

            <h2>9. Modifications des CGU</h2>
            <p>
                Bia Namur se réserve le droit de modifier les présentes CGU à tout moment.
                La date de dernière mise à jour est indiquée en haut de cette page. Les utilisateurs
                inscrits seront informés par email des modifications substantielles.
            </p>

            <h2>10. Loi applicable et juridiction</h2>
            <p>
                Les présentes CGU sont soumises à la loi belge. Tout litige relatif à leur
                interprétation ou à leur exécution relèvera de la compétence exclusive des tribunaux
                de l'arrondissement judiciaire de Namur, après tentative de résolution amiable.
            </p>

            <p class="mt-editorial text-caption text-bia-ink-mute italic">
                Voir aussi&nbsp;:
                <Link href="/mentions-legales" class="underline hover:text-bia-primary">Mentions légales</Link>
                &middot;
                <Link href="/confidentialite" class="underline hover:text-bia-primary">Politique de confidentialité</Link>
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
.prose-legal :deep(code) {
    background: theme('colors.bia.cream-dk');
    padding: 0.1rem 0.4rem;
    border-radius: 0.25rem;
    font-size: 0.9em;
}
</style>
