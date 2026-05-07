<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
    briefs: { type: Array, required: true },
});
</script>

<template>
    <Head>
        <title>Tous les briefs</title>
    </Head>

    <AppLayout>
        <section class="container-editorial pt-editorial pb-8 max-w-3xl">
            <p class="font-sans text-caption uppercase tracking-[0.2em] text-bia-primary mb-4">
                Archives
            </p>
            <h1 class="font-serif text-h1 font-medium text-bia-ink mb-3">
                Tous les briefs
            </h1>
            <p class="prose-bia max-w-reading">
                Chaque vendredi soir, cinq à sept sélections curatées pour la semaine à venir.
                Les anciens briefs restent accessibles pour ceux qui veulent retrouver une expo manquée.
            </p>
        </section>

        <section class="container-editorial py-8 max-w-3xl">
            <ul v-if="briefs.length" class="divide-y divide-bia-cream-dk">
                <li v-for="b in briefs" :key="b.slug" class="py-6 first:pt-0">
                    <Link :href="`/brief/${b.slug}`" class="group block">
                        <div class="flex items-baseline justify-between gap-4 mb-2">
                            <p class="font-sans text-caption uppercase tracking-widest text-bia-ink-mute">
                                {{ b.year }} · semaine {{ b.week_number }}
                            </p>
                            <span
                                v-if="b.status !== 'published'"
                                class="text-xs italic text-bia-ink-mute"
                            >
                                {{ b.status === 'draft_ai' ? 'Brouillon IA' : 'À relire' }}
                            </span>
                        </div>
                        <h2 class="font-serif text-h3 font-medium text-bia-ink group-hover:text-bia-primary transition-colors mb-2">
                            {{ b.title }}
                        </h2>
                        <p class="text-body text-bia-ink-soft leading-relaxed">{{ b.intro }}</p>
                    </Link>
                </li>
            </ul>
            <div v-else class="rounded-card border border-bia-cream-dk bg-white p-8 text-center">
                <p class="font-serif italic text-bia-ink-soft">
                    Aucun brief publié pour l'instant. Reviens vendredi soir.
                </p>
            </div>
        </section>
    </AppLayout>
</template>
