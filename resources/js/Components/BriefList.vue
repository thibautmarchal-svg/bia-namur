<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    items: { type: Array, required: true },
    title: { type: String, default: null },
    intro: { type: String, default: null },
});
</script>

<template>
    <article class="brief-list">
        <header v-if="title || intro" class="mb-editorial">
            <h2 v-if="title" class="font-serif text-h2 font-medium text-bia-ink mb-4">{{ title }}</h2>
            <p v-if="intro" class="prose-bia max-w-reading">{{ intro }}</p>
        </header>

        <ol class="space-y-editorial">
            <li v-for="item in items" :key="item.id" class="brief-item">
                <div class="flex gap-5 sm:gap-7">
                    <span
                        class="font-serif text-h2 font-medium text-bia-primary leading-none shrink-0 select-none"
                        aria-hidden="true"
                    >
                        {{ String(item.position).padStart(2, '0') }}
                    </span>

                    <div class="flex-1 max-w-reading">
                        <p
                            v-if="item.venue"
                            class="font-sans text-caption uppercase tracking-[0.18em] text-bia-ink-mute mb-2"
                        >
                            {{ item.venue }}<template v-if="item.when_text"> · {{ item.when_text }}</template>
                        </p>

                        <div
                            class="font-serif text-body text-bia-ink leading-relaxed brief-text"
                            v-html="renderInlineMarkdown(item.text)"
                        />

                        <p v-if="item.place" class="mt-3">
                            <Link
                                :href="`/lieu/${item.place.slug}`"
                                class="text-caption text-bia-primary hover:text-bia-primary-dk underline-offset-4 hover:underline"
                            >
                                Découvrir {{ item.place.name }} →
                            </Link>
                        </p>
                    </div>
                </div>
            </li>
        </ol>
    </article>
</template>

<script>
/** Rendu markdown minimal pour les items de brief : **bold**, _italic_, retours ligne. */
function renderInlineMarkdown(raw) {
    if (!raw) return '';
    const escaped = raw
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
    return escaped
        .replace(/\*\*(.+?)\*\*/g, '<strong class="text-bia-ink">$1</strong>')
        .replace(/_(.+?)_/g, '<em class="font-serif italic text-bia-ink-soft">$1</em>')
        .replace(/\n/g, '<br>');
}
export default { methods: { renderInlineMarkdown } };
</script>

<style scoped>
.brief-item + .brief-item {
    border-top: 1px solid theme('colors.bia.cream-dk');
    padding-top: theme('spacing.editorial');
}
.brief-text :deep(strong) {
    font-weight: 600;
}
</style>
