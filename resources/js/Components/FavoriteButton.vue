<script setup>
import { computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

const props = defineProps({
    type: { type: String, required: true, validator: (v) => ['place', 'story'].includes(v) },
    id: { type: Number, required: true },
    label: { type: String, default: null },
    variant: {
        type: String,
        default: 'icon', // 'icon' (rond compact) ou 'pill' (texte + cœur)
        validator: (v) => ['icon', 'pill'].includes(v),
    },
});

const page = usePage();

const user = computed(() => page.props.auth?.user ?? null);
const ids = computed(() => {
    const fav = page.props.auth?.favorites;
    if (!fav) return [];
    return props.type === 'place' ? (fav.places ?? []) : (fav.stories ?? []);
});
const isFavorite = computed(() => ids.value.includes(props.id));

const toggle = (event) => {
    event.preventDefault();
    event.stopPropagation();

    if (! user.value) {
        router.visit('/login', {
            data: { redirect: window.location.pathname },
        });
        return;
    }

    router.post('/favoris/toggle', { type: props.type, id: props.id }, {
        preserveScroll: true,
        preserveState: true,
        only: ['auth', 'flash'],
    });
};

const ariaLabel = computed(() => {
    if (props.label) return props.label;
    return isFavorite.value ? 'Retirer des favoris' : 'Ajouter aux favoris';
});
</script>

<template>
    <button
        type="button"
        :aria-label="ariaLabel"
        :aria-pressed="isFavorite"
        :title="ariaLabel"
        @click="toggle"
        :class="[
            'fav-btn inline-flex items-center justify-center transition-all',
            variant === 'icon'
                ? 'rounded-pill p-2'
                : 'rounded-pill px-4 py-2 gap-2 text-caption',
            isFavorite
                ? 'fav-btn--active'
                : 'fav-btn--inactive',
        ]"
    >
        <svg
            v-if="isFavorite"
            class="w-5 h-5"
            viewBox="0 0 24 24"
            fill="currentColor"
            aria-hidden="true"
        >
            <path d="M12 21s-7.5-4.7-9.6-9.3C1 8.4 3 5 6.4 5c2 0 3.6 1.1 4.6 2.6C12 6.1 13.6 5 15.6 5 19 5 21 8.4 19.6 11.7 17.5 16.3 12 21 12 21z"/>
        </svg>
        <svg
            v-else
            class="w-5 h-5"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
            aria-hidden="true"
        >
            <path d="M12 21s-7.5-4.7-9.6-9.3C1 8.4 3 5 6.4 5c2 0 3.6 1.1 4.6 2.6C12 6.1 13.6 5 15.6 5 19 5 21 8.4 19.6 11.7 17.5 16.3 12 21 12 21z"/>
        </svg>
        <span v-if="variant === 'pill'">
            {{ isFavorite ? 'Favori' : 'Ajouter aux favoris' }}
        </span>
    </button>
</template>

<style scoped>
.fav-btn {
    background-color: rgba(255, 255, 255, 0.92);
    color: theme('colors.bia.ink-soft');
    backdrop-filter: blur(8px);
    border: 1px solid theme('colors.bia.cream-dk');
}
.fav-btn:hover {
    color: theme('colors.bia.primary');
    border-color: theme('colors.bia.primary');
}
.fav-btn--active {
    color: theme('colors.bia.primary');
    border-color: theme('colors.bia.primary');
}
.fav-btn--active:hover {
    background-color: theme('colors.bia.cream');
}
:global(.dark) .fav-btn {
    background-color: rgba(42, 32, 24, 0.85);
    color: var(--bia-text-soft);
    border-color: var(--bia-border);
}
:global(.dark) .fav-btn--active {
    color: var(--bia-accent);
    border-color: var(--bia-accent);
}
</style>
