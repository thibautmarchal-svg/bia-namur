<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
</script>

<template>
    <Head>
        <title>Merci — Bia Namur</title>
    </Head>

    <AppLayout>
        <section class="container-editorial pt-editorial pb-editorial max-w-2xl">
            <p class="font-sans text-caption uppercase tracking-[0.2em] text-bia-primary mb-4">
                Bien reçu
            </p>
            <h1 class="font-serif text-hero font-medium text-bia-ink mb-6">
                C'est bia, merci.
            </h1>
            <p class="prose-bia text-lg max-w-reading mb-6">
                Ta suggestion est arrivée. On la regarde sous quelques jours — si elle est dans
                le ton et que les infos tiennent, elle rejoindra la carte sentimentale.
            </p>
            <p class="prose-bia max-w-reading mb-10">
                Si tu nous as laissé ton email, on te tiendra au courant. Sinon,
                garde un œil sur la carte, ton lieu y apparaîtra peut-être bientôt.
            </p>
            <div class="flex flex-wrap items-center gap-4">
                <Link href="/lieux" class="btn-primary">
                    Voir les lieux publiés
                </Link>
                <Link href="/contribuer" class="btn-ghost text-caption">
                    Suggérer un autre lieu
                </Link>
            </div>
        </section>
    </AppLayout>
</template>
