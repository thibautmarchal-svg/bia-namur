<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
    stories: { type: Array, required: true },
});

const TYPE_LABELS = {
    place: 'Histoire d\'un lieu',
    tradition: 'Tradition',
    wallon: 'Wallon namurois',
    patrimoine: 'Patrimoine',
};
</script>

<template>
    <Head>
        <title>Toutes les stories</title>
    </Head>

    <AppLayout>
        <section class="container-editorial pt-editorial pb-8 max-w-3xl">
            <p class="font-sans text-caption uppercase tracking-[0.2em] text-bia-primary mb-4">
                Stories
            </p>
            <h1 class="font-serif text-h1 font-medium text-bia-ink mb-3">
                Le patrimoine, raconté
            </h1>
            <p class="prose-bia max-w-reading">
                Récits de patrimoine, anecdotes par lieu, expressions du wallon namurois.
                Pas un cours d'histoire — des histoires qu'on aimerait entendre au comptoir.
            </p>
        </section>

        <section class="container-editorial py-8 max-w-3xl">
            <ul v-if="stories.length" class="divide-y divide-bia-cream-dk">
                <li v-for="story in stories" :key="story.slug" class="py-8 first:pt-0">
                    <Link :href="`/story/${story.slug}`" class="group block">
                        <p class="font-sans text-caption uppercase tracking-widest text-bia-ink-mute mb-2">
                            {{ TYPE_LABELS[story.type] ?? story.type }}
                            <template v-if="story.reading_minutes"> · {{ story.reading_minutes }} min</template>
                        </p>
                        <h2 class="font-serif text-h2 font-medium text-bia-ink group-hover:text-bia-primary transition-colors mb-3">
                            {{ story.title }}
                        </h2>
                        <p class="text-body text-bia-ink-soft leading-relaxed max-w-reading">{{ story.excerpt }}</p>
                    </Link>
                </li>
            </ul>
            <p v-else class="font-serif italic text-bia-ink-soft">Aucune story publiée pour l'instant.</p>
        </section>
    </AppLayout>
</template>
