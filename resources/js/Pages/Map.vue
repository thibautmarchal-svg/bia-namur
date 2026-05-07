<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import MapView from '@/Components/MapView.vue';

defineProps({
    city: { type: Object, required: true },
    places: { type: Array, required: true },
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
                Tous les lieux de Bia Namur sur la carte. Filtre par type ou quartier,
                ou demande à voir ce qu'il y a autour de toi.
            </p>
        </section>

        <section class="container-editorial py-8">
            <MapView
                :places="places"
                :center="city.center"
                :bounding-box="city.bounding_box"
                :types-available="city.types_available"
                :neighborhoods-available="city.neighborhoods_available"
            />
        </section>
    </AppLayout>
</template>
