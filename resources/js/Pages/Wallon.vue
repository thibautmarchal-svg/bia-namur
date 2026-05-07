<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    words: { type: Array, required: true },
    families: { type: Object, required: true },
    externalLinks: { type: Array, default: () => [] },
    stories: { type: Array, default: () => [] },
});

const wordsByFamily = computed(() => {
    const groups = {};
    for (const w of props.words) {
        const fam = w.family || 'autre';
        if (! groups[fam]) groups[fam] = [];
        groups[fam].push(w);
    }
    return groups;
});

const familyOrder = ['expression', 'quotidien', 'tradition', 'autre'];
const orderedFamilies = computed(() =>
    familyOrder.filter((fam) => wordsByFamily.value[fam]?.length > 0)
);
</script>

<template>
    <Head>
        <title>Le wallon namurois</title>
        <meta name="description" content="Quelques mots de wallon namurois pour mieux comprendre Bia Namur. Bia, biesse, à l'aise, pèkèt, Bia Bouquet — un mini-lexique du quotidien." />
    </Head>

    <AppLayout>
        <section class="container-editorial pt-editorial pb-8 max-w-3xl">
            <p class="font-sans text-caption uppercase tracking-[0.2em] text-bia-primary mb-4">
                Wallon namurois
            </p>
            <h1 class="font-serif text-h1 font-medium text-bia-ink mb-3">
                Quelques mots qui valent un détour.
            </h1>
            <p class="prose-bia max-w-reading">
                Le wallon namurois ne se parle plus dans la rue, mais ses mots survivent
                dans les expressions, les noms de fêtes, les blagues de famille. On en glisse
                quelques-uns dans le carnet — voici ceux qu'on utilise le plus.
            </p>
            <p class="prose-bia max-w-reading mt-reading">
                Pas un cours, pas un dictionnaire. Juste de quoi reconnaître <em class="font-serif italic">à l'aise</em>
                quand un voisin te dit <em class="font-serif italic">c'est bia</em>.
            </p>
        </section>

        <section
            v-for="family in orderedFamilies"
            :key="family"
            class="container-editorial py-8 max-w-3xl"
        >
            <h2 class="font-serif text-h2 font-medium text-bia-ink mb-6">
                {{ families[family] || family }}
            </h2>
            <ul class="space-y-6">
                <li
                    v-for="w in wordsByFamily[family]"
                    :key="w.word"
                    class="border-l-2 border-bia-primary pl-5 max-w-reading"
                >
                    <p class="font-serif text-h3 font-medium text-bia-ink mb-1">
                        {{ w.word }}
                        <span class="font-sans text-caption text-bia-ink-mute font-normal italic ml-2">
                            — {{ w.definition }}
                        </span>
                    </p>
                    <p class="font-serif italic text-body text-bia-ink-soft mt-2">
                        « {{ w.example }} »
                    </p>
                    <p v-if="w.note" class="text-caption text-bia-ink-mute mt-2 leading-relaxed">
                        {{ w.note }}
                    </p>
                </li>
            </ul>
        </section>

        <section v-if="stories.length" class="container-editorial py-editorial border-t border-bia-cream-dk">
            <p class="font-sans text-caption uppercase tracking-[0.2em] text-bia-primary mb-2">
                Pour aller plus loin
            </p>
            <h2 class="font-serif text-h2 font-medium text-bia-ink mb-8">
                Les stories autour du wallon
            </h2>
            <div class="grid gap-6 sm:grid-cols-2 max-w-reading">
                <article v-for="story in stories" :key="story.slug">
                    <h3 class="font-serif text-h3 font-medium mb-2">
                        <Link :href="`/story/${story.slug}`" class="hover:text-bia-primary transition-colors">
                            {{ story.title }}
                        </Link>
                    </h3>
                    <p class="text-body text-bia-ink-soft leading-relaxed">{{ story.excerpt }}</p>
                </article>
            </div>
        </section>

        <section v-if="externalLinks.length" class="container-editorial py-editorial border-t border-bia-cream-dk">
            <h2 class="font-serif text-h3 font-medium text-bia-ink mb-4">
                Si tu veux creuser
            </h2>
            <ul class="space-y-3 max-w-reading">
                <li v-for="link in externalLinks" :key="link.url">
                    <a
                        :href="link.url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="text-bia-primary hover:text-bia-primary-dk underline-offset-4 hover:underline"
                    >
                        {{ link.label }} →
                    </a>
                    <p class="text-caption text-bia-ink-mute italic mt-1">{{ link.description }}</p>
                </li>
            </ul>
        </section>
    </AppLayout>
</template>
