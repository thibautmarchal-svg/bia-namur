<script setup>
defineProps({
    source: { type: String, default: 'opendata' },
    /** Affichage compact (badge minimaliste) ou complet (avec disclaimer indicatif). */
    variant: { type: String, default: 'compact', validator: (v) => ['compact', 'full'].includes(v) },
});

const SOURCES = {
    opendata: {
        label: 'Données : data.namur.be',
        url: 'https://data.namur.be',
        license: 'CC BY 4.0',
        licenseUrl: 'https://creativecommons.org/licenses/by/4.0/',
        disclaimer: 'Informations fournies à titre indicatif. Vérifiez auprès de l\'organisateur avant déplacement.',
    },
    rss_delta: {
        label: 'Source : Le Delta',
        url: 'https://www.ledelta.be',
        license: null,
        licenseUrl: null,
        disclaimer: null,
    },
    rss_belvedere: {
        label: 'Source : Belvédère',
        url: 'https://www.belvedere-namur.be',
        license: null,
        licenseUrl: null,
        disclaimer: null,
    },
    contribution: {
        label: 'Contribution communautaire',
        url: null,
        license: null,
        licenseUrl: null,
        disclaimer: 'Suggéré par un namurois, validé par notre modération.',
    },
};
</script>

<template>
    <div :class="variant === 'full' ? 'space-y-2' : ''">
        <p
            class="inline-flex items-center gap-1.5 text-xs text-bia-ink-mute"
            aria-label="Source des données"
        >
            <svg
                class="w-3.5 h-3.5 shrink-0"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.5"
                aria-hidden="true"
            >
                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
            </svg>
            <a
                v-if="SOURCES[source]?.url"
                :href="SOURCES[source].url"
                target="_blank"
                rel="noopener noreferrer"
                class="hover:text-bia-primary underline-offset-2 hover:underline transition-colors"
            >
                {{ SOURCES[source]?.label ?? source }}
            </a>
            <span v-else>{{ SOURCES[source]?.label ?? source }}</span>
            <template v-if="SOURCES[source]?.license">
                <span aria-hidden="true">·</span>
                <a
                    :href="SOURCES[source].licenseUrl"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="hover:text-bia-primary underline-offset-2 hover:underline transition-colors"
                >
                    {{ SOURCES[source].license }}
                </a>
            </template>
        </p>
        <p
            v-if="variant === 'full' && SOURCES[source]?.disclaimer"
            class="text-xs italic text-bia-ink-mute leading-relaxed"
        >
            {{ SOURCES[source].disclaimer }}
        </p>
    </div>
</template>
