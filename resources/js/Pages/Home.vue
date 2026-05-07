<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import EditorialHero from '@/Components/EditorialHero.vue';
import PlaceCard from '@/Components/PlaceCard.vue';
import BriefList from '@/Components/BriefList.vue';

const props = defineProps({
    brief: { type: Object, default: null },
    highlightPlaces: { type: Array, default: () => [] },
    latestStories: { type: Array, default: () => [] },
});

const placesArray = props.highlightPlaces?.data ?? props.highlightPlaces ?? [];
</script>

<template>
    <Head>
        <title>Le carnet vivant des namurois</title>
        <meta name="description" content="Bia Namur — un compagnon hyperlocal éditorial pour les Namurois. Brief hebdo curaté, carte sentimentale, stories du patrimoine." />
    </Head>

    <AppLayout>
        <!-- Brief en cours -->
        <template v-if="brief">
            <EditorialHero
                :eyebrow="`Brief de la semaine ${brief.year}-W${String(brief.week_number).padStart(2, '0')}`"
                :title="brief.title"
            >
                <template #intro>
                    <p class="prose-bia text-lg max-w-reading">{{ brief.intro_text }}</p>
                </template>
                <template #actions>
                    <Link href="/briefs" class="btn-ghost text-caption">
                        Voir les briefs précédents →
                    </Link>
                </template>
            </EditorialHero>

            <section class="container-editorial py-8 max-w-3xl">
                <BriefList :items="brief.items?.data ?? brief.items ?? []" />
            </section>
        </template>

        <!-- Pas encore de brief : hero pré-lancement -->
        <template v-else>
            <EditorialHero
                eyebrow="On prépare le premier brief"
                title="Le carnet vivant"
                title-accent="des namurois."
            >
                <template #intro>
                    <p class="prose-bia text-lg max-w-reading">
                        Un compagnon hyperlocal pour celles et ceux qui aiment Namur. Chaque vendredi soir,
                        un brief de cinq à sept choses à vivre dans la semaine. Une carte sentimentale des
                        bonnes adresses. Et les stories du patrimoine — la rue Saintraint, l'origine du
                        Bia Bouquet, les souterrains de la Citadelle.
                    </p>
                </template>
            </EditorialHero>
        </template>

        <!-- Highlights de lieux -->
        <section v-if="placesArray.length" class="container-editorial py-editorial border-t border-bia-cream-dk">
            <div class="flex items-end justify-between gap-4 mb-8">
                <div>
                    <p class="font-sans text-caption uppercase tracking-[0.2em] text-bia-primary mb-2">
                        Des lieux
                    </p>
                    <h2 class="font-serif text-h2 font-medium text-bia-ink">
                        Quelques bonnes adresses
                    </h2>
                </div>
                <Link href="/lieux" class="text-caption text-bia-primary underline-offset-4 hover:underline shrink-0">
                    Tous les lieux →
                </Link>
            </div>
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <PlaceCard v-for="place in placesArray" :key="place.id ?? place.slug" :place="place" />
            </div>
        </section>

        <!-- Stories -->
        <section v-if="latestStories.length" class="container-editorial py-editorial border-t border-bia-cream-dk">
            <p class="font-sans text-caption uppercase tracking-[0.2em] text-bia-primary mb-2">
                Des stories
            </p>
            <h2 class="font-serif text-h2 font-medium text-bia-ink mb-8">
                Le patrimoine, raconté
            </h2>
            <div class="grid gap-8 sm:grid-cols-2">
                <article v-for="story in latestStories" :key="story.slug" class="max-w-reading">
                    <h3 class="font-serif text-h3 font-medium mb-3">
                        <Link :href="`/story/${story.slug}`" class="hover:text-bia-primary transition-colors">
                            {{ story.title }}
                        </Link>
                    </h3>
                    <p class="text-body text-bia-ink-soft leading-relaxed">{{ story.excerpt }}</p>
                    <Link :href="`/story/${story.slug}`" class="mt-3 inline-block text-caption text-bia-primary underline-offset-4 hover:underline">
                        Lire la suite →
                    </Link>
                </article>
            </div>
        </section>
    </AppLayout>
</template>
