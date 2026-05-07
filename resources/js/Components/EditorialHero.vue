<script setup>
import PhotoCredit from '@/Components/PhotoCredit.vue';

defineProps({
    eyebrow: { type: String, default: null },
    title: { type: String, required: true },
    titleAccent: { type: String, default: null },
    intro: { type: String, default: null },
    /** Payload PhotoResolver::for() */
    photo: { type: Object, default: null },
});
</script>

<template>
    <section class="editorial-hero">
        <div v-if="photo" class="editorial-hero__media">
            <picture>
                <source
                    v-if="photo.srcset && photo.srcset.includes('.webp')"
                    :srcset="photo.srcset"
                    :sizes="photo.sizes"
                    type="image/webp"
                />
                <img
                    :src="photo.src_jpg || photo.url"
                    :alt="photo.alt"
                    class="w-full h-full object-cover"
                    loading="eager"
                    decoding="async"
                />
            </picture>
            <div
                v-if="photo.credit"
                class="absolute bottom-3 right-4 max-w-xs bg-bia-cream/90 backdrop-blur-sm rounded-pill px-3 py-1 shadow-sm"
            >
                <PhotoCredit :photo="photo" variant="compact" />
            </div>
        </div>

        <div class="container-editorial pt-editorial pb-12">
            <p
                v-if="eyebrow"
                class="font-sans text-caption uppercase tracking-[0.2em] text-bia-primary mb-6"
            >
                {{ eyebrow }}
            </p>

            <h1 class="font-serif text-hero font-medium text-bia-ink mb-6">
                <span>{{ title }}</span>
                <template v-if="titleAccent">
                    <br>
                    <span class="text-bia-primary">{{ titleAccent }}</span>
                </template>
            </h1>

            <p v-if="intro" class="prose-bia text-lg max-w-reading">
                {{ intro }}
            </p>
            <slot v-else name="intro" />

            <div v-if="$slots.actions" class="mt-8 flex flex-wrap items-center gap-4">
                <slot name="actions" />
            </div>
        </div>
    </section>
</template>

<style scoped>
.editorial-hero {
    position: relative;
}
.editorial-hero__media {
    position: relative;
    width: 100%;
    aspect-ratio: 16 / 7;
    max-height: 60vh;
    overflow: hidden;
    background: theme('colors.bia.cream-dk');
}
@media (min-width: 768px) {
    .editorial-hero__media {
        aspect-ratio: 21 / 9;
    }
}
</style>
