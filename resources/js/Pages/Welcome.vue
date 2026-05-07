<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import EditorialHero from '@/Components/EditorialHero.vue';
</script>

<template>
    <Head>
        <title>Le carnet vivant des namurois</title>
        <meta name="description" content="Bia Namur — un compagnon hyperlocal éditorial pour les Namurois. Brief hebdo curaté, carte sentimentale, stories du patrimoine." />
    </Head>

    <AppLayout>
        <EditorialHero
            eyebrow="On prépare le premier brief"
            title="Le carnet vivant"
            title-accent="des namurois."
        >
            <template #intro>
                <p class="prose-bia text-lg max-w-reading">
                    Un compagnon hyperlocal pour celles et ceux qui aiment Namur. Chaque vendredi soir,
                    un brief de cinq à sept choses à vivre dans la semaine. Une carte sentimentale des
                    bonnes adresses, terrasses au soleil le matin, banc avec vue sur le confluent,
                    boulangerie ouverte le dimanche. Et les stories du patrimoine — la rue Saintraint,
                    l'origine du Bia Bouquet, les souterrains de la Citadelle.
                </p>
                <p class="prose-bia mt-reading max-w-reading">
                    Pas un guide touristique. Pas un agenda exhaustif.
                    Une <em class="font-serif italic text-bia-ink">sélection avec un ton</em>.
                </p>
            </template>
        </EditorialHero>

        <section class="container-editorial py-12">
            <h2 class="font-serif text-h2 mb-8">Les trois piliers du carnet</h2>
            <div class="grid gap-6 sm:grid-cols-3">
                <article class="rounded-card border-l-2 border-bia-primary bg-white pl-6 pr-5 py-6 shadow-editorial">
                    <p class="font-sans text-caption uppercase tracking-widest text-bia-primary mb-3">01</p>
                    <h3 class="font-serif text-h3 mb-3">Le brief hebdo</h3>
                    <p class="text-body text-bia-ink-soft leading-relaxed">
                        Cinq à sept sélections curatées chaque vendredi soir.
                        Un vernissage au Belvédère, le retour des asperges au marché Saint-Aubain,
                        une expo à la Citadelle. Pas l'agenda complet — l'essentiel.
                    </p>
                </article>
                <article class="rounded-card border-l-2 border-bia-primary bg-white pl-6 pr-5 py-6 shadow-editorial">
                    <p class="font-sans text-caption uppercase tracking-widest text-bia-primary mb-3">02</p>
                    <h3 class="font-serif text-h3 mb-3">La carte sentimentale</h3>
                    <p class="text-body text-bia-ink-soft leading-relaxed">
                        Les bonnes adresses, filtrables par mood, par quartier.
                        Bar à pèkèt méconnu, librairie indé, boucher de quartier.
                        Chaque lieu avec sa fiche, sa story, ses infos pratiques.
                    </p>
                </article>
                <article class="rounded-card border-l-2 border-bia-primary bg-white pl-6 pr-5 py-6 shadow-editorial">
                    <p class="font-sans text-caption uppercase tracking-widest text-bia-primary mb-3">03</p>
                    <h3 class="font-serif text-h3 mb-3">Les stories</h3>
                    <p class="text-body text-bia-ink-soft leading-relaxed">
                        Récits de patrimoine et anecdotes par lieu.
                        Le wallon namurois, ses expressions, son vocabulaire.
                        Les archives illustrées par les cartes postales d'époque.
                    </p>
                </article>
            </div>
        </section>

        <section class="container-editorial py-12">
            <div class="rounded-card border border-bia-primary/20 bg-bia-primary/5 p-6 sm:p-8">
                <p class="font-serif italic text-bia-ink-soft text-body">
                    Pour les Namurois — et pour celles et ceux qui aiment Namur.
                    Pas pour les touristes de passage.
                </p>
            </div>
        </section>
    </AppLayout>
</template>
