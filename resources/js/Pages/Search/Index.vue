<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch, nextTick, onMounted } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import PlaceCard from '@/Components/PlaceCard.vue';

const props = defineProps({
    query: { type: String, default: '' },
    results: { type: Object, required: true },
    minLength: { type: Number, default: 2 },
});

const inputQuery = ref(props.query);
const inputEl = ref(null);

const submit = () => {
    const q = inputQuery.value.trim();
    router.get('/recherche', q ? { q } : {}, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

// Debounce auto-submit on type
let timer = null;
watch(inputQuery, (next) => {
    clearTimeout(timer);
    if (next === props.query) return;
    timer = setTimeout(submit, 350);
});

onMounted(() => {
    nextTick(() => inputEl.value?.focus());
});

const hasResults = () => props.results.total > 0;
const hasQuery = () => props.query.length >= props.minLength;
</script>

<template>
    <Head>
        <title>Recherche</title>
        <meta name="robots" content="noindex" />
    </Head>

    <AppLayout>
        <section class="container-editorial pt-editorial pb-8">
            <p class="font-sans text-caption uppercase tracking-[0.2em] text-bia-primary mb-4">
                Recherche
            </p>
            <h1 class="font-serif text-h1 font-medium text-bia-ink mb-6">
                Que cherches-tu&nbsp;?
            </h1>

            <form @submit.prevent="submit" role="search" class="max-w-reading">
                <label for="search-input" class="sr-only">Rechercher</label>
                <div class="relative">
                    <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-bia-ink-mute" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <circle cx="11" cy="11" r="7"/>
                        <path d="m21 21-4.3-4.3"/>
                    </svg>
                    <input
                        id="search-input"
                        ref="inputEl"
                        v-model="inputQuery"
                        type="search"
                        autocomplete="off"
                        placeholder="Un lieu, une rue, un mot wallon, une tradition…"
                        class="w-full rounded-card border border-bia-cream-dk bg-white pl-12 pr-4 py-3 text-body text-bia-ink placeholder:text-bia-ink-mute focus:border-bia-primary focus:outline-none transition-colors"
                    />
                </div>
                <p class="mt-2 text-caption text-bia-ink-mute italic">
                    Tape au moins {{ minLength }} lettres. La recherche se lance toute seule.
                </p>
            </form>
        </section>

        <section v-if="hasQuery() && hasResults()" class="container-editorial pb-editorial space-y-editorial">
            <p class="text-caption text-bia-ink-mute">
                {{ results.total }} résultat{{ results.total > 1 ? 's' : '' }} pour
                <span class="text-bia-ink">«&nbsp;{{ query }}&nbsp;»</span>
            </p>

            <div v-if="results.places.length">
                <h2 class="font-serif text-h2 font-medium text-bia-ink mb-4">
                    Lieux <span class="text-caption text-bia-ink-mute font-sans">({{ results.places.length }})</span>
                </h2>
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <PlaceCard v-for="place in results.places" :key="place.id" :place="place" />
                </div>
            </div>

            <div v-if="results.stories.length">
                <h2 class="font-serif text-h2 font-medium text-bia-ink mb-4">
                    Stories <span class="text-caption text-bia-ink-mute font-sans">({{ results.stories.length }})</span>
                </h2>
                <ul class="divide-y divide-bia-cream-dk border-y border-bia-cream-dk">
                    <li v-for="story in results.stories" :key="story.id">
                        <Link :href="`/story/${story.slug}`" class="flex items-start gap-4 py-5 hover:bg-bia-cream-dk/40 transition-colors px-2 -mx-2 rounded-card">
                            <picture v-if="story.cover_photo" class="w-20 h-20 sm:w-28 sm:h-28 flex-shrink-0">
                                <source
                                    v-if="story.cover_photo.srcset && story.cover_photo.srcset.includes('.webp')"
                                    :srcset="story.cover_photo.srcset"
                                    type="image/webp"
                                />
                                <img
                                    :src="story.cover_photo.src_jpg || story.cover_photo.url"
                                    :alt="story.cover_photo.alt || story.title"
                                    class="w-20 h-20 sm:w-28 sm:h-28 object-cover rounded-card"
                                    loading="lazy"
                                />
                            </picture>
                            <div
                                v-else
                                class="w-20 h-20 sm:w-28 sm:h-28 flex items-center justify-center bg-bia-cream-dk rounded-card font-serif italic text-bia-ink-mute flex-shrink-0"
                                aria-hidden="true"
                            >
                                Bia
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="font-sans text-caption uppercase tracking-[0.2em] text-bia-primary mb-1">
                                    {{ story.type }}
                                </p>
                                <h3 class="font-serif text-h3 font-medium text-bia-ink mb-1 truncate">
                                    {{ story.title }}
                                </h3>
                                <p class="text-body text-bia-ink-soft line-clamp-2">{{ story.excerpt }}</p>
                            </div>
                        </Link>
                    </li>
                </ul>
            </div>

            <div v-if="results.briefs.length">
                <h2 class="font-serif text-h2 font-medium text-bia-ink mb-4">
                    Briefs <span class="text-caption text-bia-ink-mute font-sans">({{ results.briefs.length }})</span>
                </h2>
                <ul class="divide-y divide-bia-cream-dk border-y border-bia-cream-dk">
                    <li v-for="brief in results.briefs" :key="brief.id">
                        <Link :href="`/brief/${brief.slug}`" class="flex items-start gap-4 py-5 hover:bg-bia-cream-dk/40 transition-colors px-2 -mx-2 rounded-card">
                            <div class="min-w-0 flex-1">
                                <p class="font-sans text-caption uppercase tracking-[0.2em] text-bia-primary mb-1">
                                    Semaine {{ brief.week_number }} · {{ brief.year }}
                                </p>
                                <h3 class="font-serif text-h3 font-medium text-bia-ink mb-1">
                                    {{ brief.title }}
                                </h3>
                                <p class="text-body text-bia-ink-soft line-clamp-2">{{ brief.intro_text }}</p>
                            </div>
                        </Link>
                    </li>
                </ul>
            </div>
        </section>

        <section v-else-if="hasQuery() && !hasResults()" class="container-editorial pb-editorial">
            <div class="rounded-card border border-bia-cream-dk bg-bia-cream-dk/30 p-6 sm:p-8 max-w-reading">
                <p class="font-serif italic text-bia-ink-soft text-h3 mb-2">
                    Rien trouvé pour «&nbsp;{{ query }}&nbsp;».
                </p>
                <p class="text-body text-bia-ink-soft leading-relaxed mb-4">
                    Essaie une orthographe alternative, un mot plus court, ou explore les lieux et stories
                    via la navigation. Et si tu connais une bonne adresse qui manque&nbsp;:
                </p>
                <Link href="/contribuer" class="btn-primary">
                    Contribuer un lieu
                </Link>
            </div>
        </section>

        <section v-else class="container-editorial pb-editorial">
            <div class="grid gap-6 sm:grid-cols-3 max-w-reading">
                <Link href="/lieux" class="rounded-card border border-bia-cream-dk p-5 hover:border-bia-primary hover:bg-bia-primary/5 transition-colors">
                    <p class="font-sans text-caption uppercase tracking-[0.2em] text-bia-primary mb-2">Carte</p>
                    <p class="font-serif text-h3 font-medium text-bia-ink">Lieux</p>
                </Link>
                <Link href="/stories" class="rounded-card border border-bia-cream-dk p-5 hover:border-bia-primary hover:bg-bia-primary/5 transition-colors">
                    <p class="font-sans text-caption uppercase tracking-[0.2em] text-bia-primary mb-2">Patrimoine</p>
                    <p class="font-serif text-h3 font-medium text-bia-ink">Stories</p>
                </Link>
                <Link href="/briefs" class="rounded-card border border-bia-cream-dk p-5 hover:border-bia-primary hover:bg-bia-primary/5 transition-colors">
                    <p class="font-sans text-caption uppercase tracking-[0.2em] text-bia-primary mb-2">Hebdo</p>
                    <p class="font-serif text-h3 font-medium text-bia-ink">Briefs</p>
                </Link>
            </div>
        </section>
    </AppLayout>
</template>
