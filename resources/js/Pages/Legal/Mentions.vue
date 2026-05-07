<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
    updatedAt: { type: String, required: true },
});
</script>

<template>
    <Head>
        <title>Mentions légales — Bia Namur</title>
        <meta name="robots" content="noindex" />
    </Head>

    <AppLayout>
        <article class="container-editorial py-editorial max-w-reading prose-legal">
            <p class="font-sans text-caption uppercase tracking-[0.2em] text-bia-primary mb-4">
                Pages légales
            </p>
            <h1 class="font-serif text-h1 font-medium text-bia-ink mb-3">Mentions légales</h1>
            <p class="text-caption text-bia-ink-mute italic mb-editorial">
                Dernière mise à jour : {{ updatedAt }}
            </p>

            <h2>Éditeur du site</h2>
            <p>
                <strong>Bia Namur</strong> est édité par&nbsp;:<br>
                <strong>Thibaut Marchal</strong>, personne physique<br>
                5080 La Bruyère, Belgique<br>
                Email&nbsp;: <a href="mailto:hello@bianamur.be">hello@bianamur.be</a>
            </p>

            <h2>Directeur de la publication</h2>
            <p>
                Thibaut Marchal, en sa qualité d'éditeur du site, assume la direction de la publication
                et de la rédaction.
            </p>

            <h2>Hébergement</h2>
            <p>
                Le site est hébergé par&nbsp;: <em>[à compléter à l'activation de l'hébergement de production]</em>.
                L'infrastructure de production sera localisée dans l'Union européenne. Les coordonnées
                complètes de l'hébergeur seront ajoutées ici dès la mise en ligne définitive.
            </p>

            <h2>Propriété intellectuelle</h2>
            <p>
                L'ensemble des contenus éditoriaux publiés sur Bia Namur (textes, briefs hebdomadaires,
                stories de patrimoine, descriptions de lieux, mises en page, identité graphique) est
                protégé par le droit d'auteur belge et international, et est la propriété exclusive
                de l'éditeur, sauf mention contraire (photos sourcées Wikimedia Commons sous licence CC,
                données issues d'OpenData Namur sous CC&nbsp;BY 4.0).
            </p>
            <p>
                Toute reproduction, totale ou partielle, sans autorisation écrite préalable, est
                interdite et constitue une contrefaçon sanctionnée par les articles XI.165 et suivants
                du Code de droit économique belge.
            </p>

            <h2>Marques et logos</h2>
            <p>
                « Bia Namur » et le logo Bia Namur (mark carré ambré, lettre B serif, trois points
                évoquant le bouquet) constituent des signes distinctifs propres à l'éditeur.
                Un dépôt de marque auprès de l'Office Bénélux de la Propriété Intellectuelle (BOIP)
                est en cours pour les classes 9, 35 et 41.
            </p>

            <h2>Données de la Ville de Namur</h2>
            <p>
                Les données issues de la plateforme OpenData de la Ville de Namur
                (<a href="https://data.namur.be" target="_blank" rel="noopener noreferrer">data.namur.be</a>)
                sont mises à disposition sous licence
                <a href="https://creativecommons.org/licenses/by/4.0/" target="_blank" rel="noopener noreferrer">Creative Commons Attribution 4.0&nbsp;CC&nbsp;BY 4.0</a>.
                Les informations communiquées sur l'OpenData n'ont aucune valeur officielle et n'engagent
                pas la Ville de Namur. Vérifiez auprès de l'organisateur avant déplacement.
            </p>

            <h2>Liens vers d'autres sites</h2>
            <p>
                Bia Namur peut contenir des liens vers des sites tiers (sites de lieux référencés,
                Wikipédia, sources OpenData). L'éditeur n'exerce aucun contrôle sur le contenu de
                ces sites et ne saurait être tenu responsable de leur contenu, de leurs pratiques
                de confidentialité ou des préjudices liés à leur consultation.
            </p>

            <h2>Contact</h2>
            <ul>
                <li>Questions éditoriales et générales&nbsp;: <a href="mailto:hello@bianamur.be">hello@bianamur.be</a></li>
                <li>Questions données personnelles (RGPD)&nbsp;: <a href="mailto:privacy@bianamur.be">privacy@bianamur.be</a></li>
            </ul>

            <h2>Loi applicable</h2>
            <p>
                Le présent site est soumis à la loi belge. En cas de litige, et après recherche
                d'une solution amiable, les tribunaux de Namur seront seuls compétents.
            </p>

            <p class="mt-editorial text-caption text-bia-ink-mute italic">
                Voir aussi&nbsp;:
                <Link href="/cgu" class="underline hover:text-bia-primary">Conditions générales d'utilisation</Link>
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
</style>
