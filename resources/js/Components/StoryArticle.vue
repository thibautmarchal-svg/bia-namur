<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import PhotoCredit from '@/Components/PhotoCredit.vue';
import FavoriteButton from '@/Components/FavoriteButton.vue';

const props = defineProps({
    story: { type: Object, required: true },
});

const TYPE_LABELS = {
    place: 'Histoire d\'un lieu',
    tradition: 'Tradition',
    wallon: 'Wallon namurois',
    patrimoine: 'Patrimoine',
};

const typeLabel = computed(() => TYPE_LABELS[props.story.type] ?? props.story.type);
const photo = computed(() => props.story.cover_photo ?? null);

/** Rendu markdown minimaliste pour une story : paragraphes, **bold**, _italic_. */
const renderedHtml = computed(() => {
    const raw = props.story.content ?? '';
    const paragraphs = raw
        .split(/\n{2,}/)
        .map((p) => p.trim())
        .filter(Boolean);

    return paragraphs
        .map((p) => {
            const escaped = p
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');
            const inline = escaped
                .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
                .replace(/\*(.+?)\*/g, '<em>$1</em>')
                .replace(/_(.+?)_/g, '<em>$1</em>')
                .replace(/\n/g, '<br>');
            return `<p>${inline}</p>`;
        })
        .join('');
});
</script>

<template>
    <article class="story-article">
        <div v-if="photo" class="story-cover">
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
        </div>

        <header class="container-editorial pt-editorial pb-8 max-w-reading">
            <p class="font-sans text-caption uppercase tracking-[0.2em] text-bia-primary mb-4">
                {{ typeLabel }}
                <template v-if="story.reading_minutes">
                    <span class="text-bia-ink-mute font-normal">· {{ story.reading_minutes }} min de lecture</span>
                </template>
            </p>
            <div class="flex items-start gap-4 mb-6">
                <h1 class="font-serif text-hero font-medium text-bia-ink leading-tight flex-1">
                    {{ story.title }}
                </h1>
                <FavoriteButton :type="'story'" :id="story.id" variant="pill" class="mt-2 shrink-0" />
            </div>
            <p v-if="story.excerpt" class="font-serif text-h3 text-bia-ink-soft italic leading-snug">
                {{ story.excerpt }}
            </p>
            <p v-if="story.place" class="mt-6 text-caption text-bia-ink-mute">
                À propos de
                <Link :href="`/lieu/${story.place.slug}`" class="underline hover:text-bia-primary">
                    {{ story.place.name }}
                </Link>
            </p>
        </header>

        <div
            class="container-editorial max-w-reading text-body text-bia-ink-soft prose-story"
            v-html="renderedHtml"
        />

        <footer class="container-editorial mt-editorial pt-8 border-t border-bia-cream-dk max-w-reading space-y-3">
            <PhotoCredit v-if="photo?.credit" :photo="photo" variant="full" />
            <p v-if="story.ai_generated" class="text-caption text-bia-ink-mute italic">
                Texte initial proposé par notre pipeline éditorial, relu et validé par un humain.
            </p>
            <p v-else class="text-caption text-bia-ink-mute italic">
                Texte rédigé pour Bia Namur. Toute reproduction nécessite une autorisation écrite.
            </p>
        </footer>
    </article>
</template>

<style scoped>
.story-cover {
    width: 100%;
    aspect-ratio: 16 / 7;
    max-height: 60vh;
    overflow: hidden;
    background: theme('colors.bia.cream-dk');
}
@media (min-width: 768px) {
    .story-cover {
        aspect-ratio: 21 / 9;
    }
}
.prose-story :deep(p) {
    margin-bottom: theme('spacing.reading');
    line-height: 1.8;
}
.prose-story :deep(p:first-child)::first-letter {
    font-family: theme('fontFamily.serif');
    font-size: 3.5rem;
    font-weight: 500;
    color: theme('colors.bia.primary');
    float: left;
    line-height: 0.85;
    margin-right: 0.5rem;
    margin-top: 0.4rem;
}
.prose-story :deep(strong) {
    font-weight: 600;
    color: theme('colors.bia.ink');
}
.prose-story :deep(em) {
    font-style: italic;
}
</style>
