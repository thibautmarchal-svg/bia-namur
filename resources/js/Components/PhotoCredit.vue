<script setup>
defineProps({
    /** Payload retourne par App\Support\PhotoResolver::for(). */
    photo: { type: Object, required: true },
    /** Affichage compact (1 ligne) ou complet (2 lignes avec source link). */
    variant: { type: String, default: 'compact', validator: (v) => ['compact', 'full'].includes(v) },
});
</script>

<template>
    <p
        v-if="photo.credit"
        :class="[
            'inline-flex items-center gap-1.5 text-xs text-bia-ink-mute',
            variant === 'full' ? 'flex-wrap' : '',
        ]"
        aria-label="Crédit photo"
    >
        <svg
            class="w-3.5 h-3.5 shrink-0"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="1.5"
            aria-hidden="true"
        >
            <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
        </svg>
        <span>
            Photo : <span class="text-bia-ink-soft">{{ photo.credit }}</span>
            <template v-if="photo.license">
                ·
                <a
                    v-if="photo.license_url"
                    :href="photo.license_url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="hover:text-bia-primary underline-offset-2 hover:underline"
                >
                    {{ photo.license }}
                </a>
                <span v-else>{{ photo.license }}</span>
            </template>
            <template v-if="variant === 'full' && photo.source_url">
                ·
                <a
                    :href="photo.source_url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="hover:text-bia-primary underline-offset-2 hover:underline"
                >
                    Source
                </a>
            </template>
        </span>
    </p>
</template>
