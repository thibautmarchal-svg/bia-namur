<script setup>
/**
 * Page de demo interne des composants UI Bia Namur.
 * Accessible uniquement en local (env=local) — gated cote backend.
 * Pas de lorem ipsum : vrais lieux namurois (factices ici, vraies seeds en J6).
 */
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import EditorialHero from '@/Components/EditorialHero.vue';
import PlaceCard from '@/Components/PlaceCard.vue';
import DataAttribution from '@/Components/DataAttribution.vue';

const samplePlaces = [
    {
        slug: 'citadelle-de-namur',
        name: 'Citadelle de Namur',
        type: 'patrimoine',
        description: 'La fortification stratégique sur les hauteurs du confluent. Promenade panoramique sur la Sambre et la Meuse, souterrains à explorer, herbe pour s\'asseoir.',
        neighborhood: 'Grognon',
        tags: ['panorama', 'famille', 'à pied'],
        cover_photo_url: null,
    },
    {
        slug: 'le-bia-bouquet',
        name: 'Le Bia Bouquet',
        type: 'bar',
        description: 'Le bistrot historique du quai, encore tenu en famille. Pèkèt maison, bières du coin, ambiance qui parle vraiment namurois.',
        neighborhood: 'Centre',
        tags: ['pèkèt', 'soir', 'tradition'],
        cover_photo_url: null,
    },
    {
        slug: 'marche-saint-aubain',
        name: 'Marché Saint-Aubain',
        type: 'marche',
        description: 'Tous les samedis matin sur la place de la cathédrale. Maraîchers, fromager bio, boucher campagnard, fleurs de saison.',
        neighborhood: 'Centre',
        tags: ['samedi matin', 'bio', 'frais'],
        cover_photo_url: null,
    },
];
</script>

<template>
    <Head>
        <title>Composants UI — Dev</title>
    </Head>

    <AppLayout>
        <section class="container-editorial pt-editorial pb-8">
            <p class="font-sans text-caption uppercase tracking-[0.2em] text-bia-accent mb-4">
                Page locale uniquement
            </p>
            <h1 class="font-serif text-h1 font-medium mb-3">
                Démo composants UI
            </h1>
            <p class="text-body text-bia-ink-soft max-w-reading">
                Aperçu visuel des composants prioritaires Bia Namur,
                rendus avec des données factices namuroises pour validation.
                Cette page n'est accessible qu'en environnement local.
            </p>
        </section>

        <section class="container-editorial py-8 border-t border-bia-cream-dk">
            <h2 class="font-serif text-h2 mb-2">&lt;EditorialHero&gt;</h2>
            <p class="text-caption text-bia-ink-mute mb-6 max-w-reading">
                Bandeau d'intro avec photo optionnelle, eyebrow, titre serif XL et accent ambré, intro 2-3 phrases.
            </p>

            <div class="rounded-card overflow-hidden border border-bia-cream-dk">
                <EditorialHero
                    eyebrow="Cette semaine à Namur"
                    title="Sept choses à vivre"
                    title-accent="entre lundi et dimanche."
                    intro="Une expo à la Citadelle, un concert au Belvédère, le retour des asperges au marché Saint-Aubain et trois autres surprises pour la semaine. À l'aise."
                />
            </div>
        </section>

        <section class="container-editorial py-8 border-t border-bia-cream-dk">
            <h2 class="font-serif text-h2 mb-2">&lt;PlaceCard&gt;</h2>
            <p class="text-caption text-bia-ink-mute mb-6 max-w-reading">
                Fiche lieu compact : photo 4:3 (placeholder serif si absente), type majuscule espacé, nom serif, mood tags, quartier.
            </p>

            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <PlaceCard v-for="place in samplePlaces" :key="place.slug" :place="place" />
            </div>
        </section>

        <section class="container-editorial py-8 border-t border-bia-cream-dk">
            <h2 class="font-serif text-h2 mb-2">&lt;DataAttribution&gt;</h2>
            <p class="text-caption text-bia-ink-mute mb-6 max-w-reading">
                Badge discret pour citer la source d'un contenu (OpenData, RSS, contribution).
            </p>

            <div class="space-y-6 max-w-reading">
                <div class="rounded-card border border-bia-cream-dk bg-white p-5">
                    <p class="text-caption uppercase tracking-widest text-bia-ink-mute mb-2">variant=&quot;compact&quot;</p>
                    <DataAttribution source="opendata" />
                </div>
                <div class="rounded-card border border-bia-cream-dk bg-white p-5">
                    <p class="text-caption uppercase tracking-widest text-bia-ink-mute mb-2">variant=&quot;full&quot; sur fiche événement</p>
                    <DataAttribution source="opendata" variant="full" />
                </div>
                <div class="rounded-card border border-bia-cream-dk bg-white p-5">
                    <p class="text-caption uppercase tracking-widest text-bia-ink-mute mb-2">source=&quot;contribution&quot;</p>
                    <DataAttribution source="contribution" variant="full" />
                </div>
                <div class="rounded-card border border-bia-cream-dk bg-white p-5">
                    <p class="text-caption uppercase tracking-widest text-bia-ink-mute mb-2">source=&quot;rss_delta&quot;</p>
                    <DataAttribution source="rss_delta" />
                </div>
            </div>
        </section>
    </AppLayout>
</template>
