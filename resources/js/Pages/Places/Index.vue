<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PlaceCard from '@/Components/PlaceCard.vue';

defineProps({
    places: { type: Object, required: true },
});
</script>

<template>
    <Head>
        <title>Tous les lieux</title>
    </Head>

    <AppLayout>
        <section class="container-editorial pt-editorial pb-8">
            <p class="font-sans text-caption uppercase tracking-[0.2em] text-bia-primary mb-4">
                Carte sentimentale
            </p>
            <h1 class="font-serif text-h1 font-medium text-bia-ink mb-3">
                Les bonnes adresses
            </h1>
            <p class="prose-bia max-w-reading">
                Terrasses au soleil le matin, banc avec vue sur le confluent, boulangerie ouverte le dimanche,
                bar à pèkèt méconnu. Une sélection avec un ton, pas un annuaire.
            </p>
        </section>

        <section class="container-editorial py-8">
            <div v-if="places.data?.length" class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <PlaceCard v-for="place in places.data" :key="place.id" :place="place" />
            </div>
            <p v-else class="font-serif italic text-bia-ink-soft">Aucun lieu publié pour l'instant.</p>
        </section>
    </AppLayout>
</template>
