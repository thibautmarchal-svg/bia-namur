<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import FavoriteButton from '@/Components/FavoriteButton.vue';

const props = defineProps({
    place: { type: Object, required: true },
    href: { type: String, default: null },
    showDistance: { type: Boolean, default: false },
});

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

const typeLabel = computed(() => TYPE_LABELS[props.place.type] ?? props.place.type);
const visibleTags = computed(() => (props.place.tags ?? []).slice(0, 3));
const linkHref = computed(() => props.href ?? `/lieu/${props.place.slug}`);
const photo = computed(() => props.place.cover_photo ?? null);
</script>

<template>
    <article class="group relative">
        <FavoriteButton
            :type="'place'"
            :id="place.id"
            class="absolute top-3 right-3 z-10"
        />
        <Link
            :href="linkHref"
            class="block rounded-card overflow-hidden bg-white border border-bia-cream-dk shadow-editorial hover:shadow-lg hover:-translate-y-0.5 transition-all duration-200"
        >
            <div class="aspect-[4/3] bg-bia-cream-dk overflow-hidden">
                <picture v-if="photo">
                    <source
                        v-if="photo.srcset && photo.srcset.includes('.webp')"
                        :srcset="photo.srcset"
                        sizes="(min-width: 1024px) 360px, (min-width: 640px) 50vw, 100vw"
                        type="image/webp"
                    />
                    <img
                        :src="photo.src_jpg || photo.url"
                        :alt="photo.alt || place.name"
                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                        loading="lazy"
                        decoding="async"
                    />
                </picture>
                <div
                    v-else
                    class="w-full h-full flex items-center justify-center text-bia-ink-mute font-serif text-h2 italic"
                    aria-hidden="true"
                >
                    Bia
                </div>
            </div>

            <div class="p-5 space-y-3">
                <p class="font-sans text-xs uppercase tracking-[0.18em] text-bia-primary">
                    {{ typeLabel }}
                </p>
                <h3 class="font-serif text-h3 font-medium text-bia-ink leading-tight line-clamp-2">
                    {{ place.name }}
                </h3>
                <p
                    v-if="place.description"
                    class="text-body text-bia-ink-soft leading-relaxed line-clamp-3"
                >
                    {{ place.description }}
                </p>
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 pt-1 text-caption text-bia-ink-mute">
                    <span v-if="place.neighborhood" class="inline-flex items-center gap-1">
                        <span aria-hidden="true">·</span>
                        {{ place.neighborhood }}
                    </span>
                    <span v-if="showDistance && place.distance_m != null" class="inline-flex items-center gap-1">
                        <span aria-hidden="true">·</span>
                        {{ Math.round(place.distance_m) }} m
                    </span>
                </div>
                <ul
                    v-if="visibleTags.length"
                    class="flex flex-wrap gap-1.5 pt-1"
                    aria-label="Mood"
                >
                    <li
                        v-for="tag in visibleTags"
                        :key="tag"
                        class="text-xs text-bia-ink-soft bg-bia-cream-dk/70 rounded-pill px-2.5 py-1"
                    >
                        {{ tag }}
                    </li>
                </ul>
            </div>
        </Link>
    </article>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
