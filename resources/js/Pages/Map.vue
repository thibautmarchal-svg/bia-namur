<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PlaceCard from '@/Components/PlaceCard.vue';

defineProps({
    city: { type: Object, required: true },
    places: { type: Object, required: true },
});
</script>

<template>
    <Head>
        <title>Carte de {{ city.name }}</title>
    </Head>

    <AppLayout>
        <section class="container-editorial pt-editorial pb-8 max-w-3xl">
            <p class="font-sans text-caption uppercase tracking-[0.2em] text-bia-primary mb-4">
                Carte sentimentale
            </p>
            <h1 class="font-serif text-h1 font-medium text-bia-ink mb-3">
                {{ city.name }}, à parcourir
            </h1>
            <p class="prose-bia max-w-reading">
                Tous les lieux de Bia Namur sur la carte. Filtrable par mood, par quartier, par moment de la journée.
            </p>
        </section>

        <!-- Stub Maplibre — la carte interactive arrive en S2 -->
        <section class="container-editorial py-8">
            <div
                class="rounded-card overflow-hidden border border-bia-cream-dk bg-bia-cream-dk/40 aspect-[16/9] sm:aspect-[21/9] flex items-center justify-center text-center px-6"
            >
                <div class="max-w-md">
                    <p class="font-sans text-caption uppercase tracking-[0.2em] text-bia-primary mb-3">
                        Bientôt
                    </p>
                    <h2 class="font-serif text-h2 font-medium text-bia-ink mb-3">
                        La carte interactive
                    </h2>
                    <p class="text-body text-bia-ink-soft leading-relaxed">
                        On prépare la carte Maplibre avec les fonds OpenStreetMap, repensée aux tons crème
                        et ambré de Bia. En attendant, voilà les lieux publiés en liste.
                    </p>
                </div>
            </div>
        </section>

        <section class="container-editorial py-8">
            <h2 class="font-serif text-h2 font-medium mb-6">
                {{ places.data?.length ?? 0 }} lieux sur la carte
            </h2>
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <PlaceCard v-for="place in places.data" :key="place.id" :place="place" />
            </div>
        </section>
    </AppLayout>
</template>
