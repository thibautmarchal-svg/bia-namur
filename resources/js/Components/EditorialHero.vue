<script setup>
defineProps({
    eyebrow: { type: String, default: null },
    title: { type: String, required: true },
    titleAccent: { type: String, default: null },
    intro: { type: String, default: null },
    photoUrl: { type: String, default: null },
    photoAlt: { type: String, default: '' },
    photoCredit: { type: String, default: null },
});
</script>

<template>
    <section class="editorial-hero">
        <div v-if="photoUrl" class="editorial-hero__media">
            <img
                :src="photoUrl"
                :alt="photoAlt"
                class="w-full h-full object-cover"
                loading="eager"
                decoding="async"
            />
            <p
                v-if="photoCredit"
                class="absolute bottom-3 right-4 text-xs text-white/80 font-sans italic mix-blend-overlay"
            >
                {{ photoCredit }}
            </p>
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
