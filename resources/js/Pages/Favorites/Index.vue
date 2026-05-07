<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import PlaceCard from '@/Components/PlaceCard.vue';
import PushOptIn from '@/Components/PushOptIn.vue';

const props = defineProps({
    places: { type: Array, required: true },
    stories: { type: Array, required: true },
    count: { type: Number, required: true },
    limit: { type: Number, required: true },
    tier: { type: String, default: 'free' },
});

const isFull = computed(() => props.count >= props.limit);
const remaining = computed(() => Math.max(0, props.limit - props.count));
</script>

<template>
    <Head>
        <title>Mes favoris</title>
        <meta name="robots" content="noindex" />
    </Head>

    <AppLayout>
        <section class="container-editorial pt-editorial pb-8">
            <p class="font-sans text-caption uppercase tracking-[0.2em] text-bia-primary mb-4">
                Mon carnet
            </p>
            <h1 class="font-serif text-h1 font-medium text-bia-ink mb-3">
                Mes favoris
            </h1>
            <p class="prose-bia max-w-reading">
                Les lieux et stories que tu as épinglés. {{ count }} sur {{ limit }}.
                <span v-if="isFull" class="text-bia-accent font-medium">Limite atteinte.</span>
                <span v-else-if="remaining <= 3" class="text-bia-ink-mute italic">Plus que {{ remaining }}.</span>
            </p>
        </section>

        <section v-if="count === 0" class="container-editorial pb-editorial">
            <div class="rounded-card border border-bia-cream-dk bg-bia-cream-dk/30 p-6 sm:p-8 max-w-reading">
                <p class="font-serif italic text-bia-ink-soft text-h3 mb-2">
                    Ton carnet est vide.
                </p>
                <p class="text-body text-bia-ink-soft leading-relaxed mb-4">
                    Clique le cœur sur un lieu ou une story pour le mettre de côté.
                    Ça reste chez toi, personne d'autre ne voit ta liste.
                </p>
                <div class="flex flex-wrap gap-3">
                    <Link href="/lieux" class="btn-primary">Explorer les lieux</Link>
                    <Link href="/stories" class="btn-ghost">Lire les stories</Link>
                </div>
            </div>
        </section>

        <section v-if="places.length" class="container-editorial pb-editorial">
            <h2 class="font-serif text-h2 font-medium text-bia-ink mb-6">
                Lieux <span class="text-caption text-bia-ink-mute font-sans">({{ places.length }})</span>
            </h2>
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <PlaceCard v-for="place in places" :key="place.id" :place="place" />
            </div>
        </section>

        <section v-if="stories.length" class="container-editorial pb-editorial">
            <h2 class="font-serif text-h2 font-medium text-bia-ink mb-6">
                Stories <span class="text-caption text-bia-ink-mute font-sans">({{ stories.length }})</span>
            </h2>
            <ul class="divide-y divide-bia-cream-dk border-y border-bia-cream-dk">
                <li v-for="story in stories" :key="story.id">
                    <Link :href="`/story/${story.slug}`" class="flex items-start gap-4 py-5 hover:bg-bia-cream-dk/40 transition-colors px-2 -mx-2 rounded-card">
                        <picture v-if="story.cover_photo" class="w-20 h-20 sm:w-28 sm:h-28 flex-shrink-0">
                            <source
                                v-if="story.cover_photo.srcset && story.cover_photo.srcset.includes('.webp')"
                                :srcset="story.cover_photo.srcset"
                                type="image/webp"
                            />
                            <img
                                :src="story.cover_photo.src_jpg || story.cover_photo.url"
                                :alt="story.cover_photo.alt || story.title"
                                class="w-20 h-20 sm:w-28 sm:h-28 object-cover rounded-card"
                                loading="lazy"
                            />
                        </picture>
                        <div
                            v-else
                            class="w-20 h-20 sm:w-28 sm:h-28 flex items-center justify-center bg-bia-cream-dk rounded-card font-serif italic text-bia-ink-mute flex-shrink-0"
                            aria-hidden="true"
                        >
                            Bia
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="font-sans text-caption uppercase tracking-[0.2em] text-bia-primary mb-1">
                                {{ story.type }}
                            </p>
                            <h3 class="font-serif text-h3 font-medium text-bia-ink mb-1 truncate">
                                {{ story.title }}
                            </h3>
                            <p class="text-body text-bia-ink-soft line-clamp-2">{{ story.excerpt }}</p>
                        </div>
                    </Link>
                </li>
            </ul>
        </section>

        <section class="container-editorial pb-editorial">
            <PushOptIn variant="card" />
        </section>

        <section v-if="isFull && tier === 'free'" class="container-editorial py-editorial border-t border-bia-cream-dk">
            <div class="rounded-card border border-bia-primary/20 bg-bia-primary/5 p-6 sm:p-8 max-w-reading">
                <p class="font-sans text-caption uppercase tracking-[0.2em] text-bia-primary mb-3">
                    Bia +
                </p>
                <h2 class="font-serif text-h3 font-medium text-bia-ink mb-3">
                    Plus de favoris bientôt.
                </h2>
                <p class="text-body text-bia-ink-soft leading-relaxed">
                    Tu as épinglé tes 20 favoris — c'est déjà un beau carnet. Bia + (à venir)
                    permettra d'aller au-delà, avec des notifications de proximité et
                    un brief personnalisé.
                </p>
            </div>
        </section>
    </AppLayout>
</template>
