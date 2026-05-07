<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataAttribution from '@/Components/DataAttribution.vue';

const props = defineProps({
    place: { type: Object, required: true },
});

const data = props.place.data ?? props.place;

const TYPE_LABELS = {
    cafe: 'Café',
    restaurant: 'Restaurant',
    bar: 'Bar',
    boulangerie: 'Boulangerie',
    librairie: 'Librairie',
    patrimoine: 'Patrimoine',
    parc: 'Parc',
    marche: 'Marché',
    culture: 'Lieu culturel',
    hidden_gem: 'Hidden gem',
};

const typeLabel = computed(() => TYPE_LABELS[data.type] ?? data.type);
const tags = computed(() => data.tags ?? []);
const openingHours = computed(() =>
    data.opening_hours
        ? Object.entries(data.opening_hours).map(([key, value]) => ({ key, value }))
        : []
);
const contact = computed(() =>
    data.contact
        ? Object.entries(data.contact).filter(([_, value]) => !!value).map(([key, value]) => ({ key, value }))
        : []
);
</script>

<template>
    <Head>
        <title>{{ data.name }}</title>
        <meta name="description" :content="data.description" />
    </Head>

    <AppLayout>
        <section class="container-editorial pt-editorial pb-8 max-w-3xl">
            <p class="font-sans text-caption uppercase tracking-[0.2em] text-bia-primary mb-4">
                {{ typeLabel }}
                <template v-if="data.neighborhood">
                    · {{ data.neighborhood }}
                </template>
            </p>
            <h1 class="font-serif text-hero font-medium text-bia-ink leading-tight mb-6">
                {{ data.name }}
            </h1>
            <p v-if="data.description" class="font-serif text-h3 text-bia-ink-soft italic leading-snug max-w-reading">
                {{ data.description }}
            </p>
        </section>

        <section class="container-editorial py-8 max-w-3xl">
            <ul v-if="tags.length" class="flex flex-wrap gap-2 mb-editorial">
                <li v-for="tag in tags" :key="tag" class="text-caption text-bia-ink-soft bg-bia-cream-dk/70 rounded-pill px-3 py-1">
                    {{ tag }}
                </li>
            </ul>

            <div class="grid gap-8 sm:grid-cols-2">
                <div v-if="data.address || openingHours.length">
                    <h2 class="font-sans text-caption uppercase tracking-widest text-bia-primary mb-3">
                        Pratique
                    </h2>
                    <p v-if="data.address" class="text-body text-bia-ink-soft mb-4">{{ data.address }}</p>
                    <dl v-if="openingHours.length" class="space-y-1">
                        <div v-for="oh in openingHours" :key="oh.key" class="flex gap-3 text-caption">
                            <dt class="text-bia-ink-mute capitalize w-32 shrink-0">{{ oh.key.replace(/_/g, ' ') }}</dt>
                            <dd class="text-bia-ink-soft">{{ oh.value }}</dd>
                        </div>
                    </dl>
                </div>

                <div v-if="contact.length">
                    <h2 class="font-sans text-caption uppercase tracking-widest text-bia-primary mb-3">
                        Contact
                    </h2>
                    <ul class="space-y-2">
                        <li v-for="c in contact" :key="c.key" class="text-caption">
                            <span class="text-bia-ink-mute capitalize mr-2">{{ c.key }}</span>
                            <a
                                v-if="c.key === 'website'"
                                :href="c.value"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="text-bia-primary hover:text-bia-primary-dk underline-offset-4 hover:underline"
                            >
                                {{ c.value.replace(/^https?:\/\//, '') }}
                            </a>
                            <a
                                v-else-if="c.key === 'email'"
                                :href="`mailto:${c.value}`"
                                class="text-bia-primary hover:text-bia-primary-dk underline-offset-4 hover:underline"
                            >
                                {{ c.value }}
                            </a>
                            <a
                                v-else-if="c.key === 'phone'"
                                :href="`tel:${c.value.replace(/\s/g, '')}`"
                                class="text-bia-primary hover:text-bia-primary-dk underline-offset-4 hover:underline"
                            >
                                {{ c.value }}
                            </a>
                            <span v-else class="text-bia-ink-soft">{{ c.value }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </section>

        <section v-if="data.story" class="container-editorial py-editorial max-w-3xl border-t border-bia-cream-dk">
            <p class="font-sans text-caption uppercase tracking-widest text-bia-primary mb-4">
                Story
            </p>
            <h2 class="font-serif text-h2 font-medium mb-4">
                <Link :href="`/story/${data.story.slug}`" class="hover:text-bia-primary transition-colors">
                    {{ data.story.title }}
                </Link>
            </h2>
            <p class="font-serif italic text-bia-ink-soft text-body max-w-reading">
                {{ data.story.excerpt }}
            </p>
            <Link :href="`/story/${data.story.slug}`" class="mt-4 inline-block text-caption text-bia-primary underline-offset-4 hover:underline">
                Lire la story →
            </Link>
        </section>

        <section class="container-editorial py-8 max-w-3xl">
            <DataAttribution :source="data.source === 'opendata' ? 'opendata' : 'contribution'" :variant="data.source === 'opendata' ? 'full' : 'compact'" />
        </section>
    </AppLayout>
</template>
