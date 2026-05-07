<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
</script>

<template>
    <Head>
        <title>À propos de Bia Namur</title>
        <meta name="description" content="Bia Namur — pourquoi ce carnet, pour qui, comment on travaille. Position éthique, modèle économique, ce qu'on ne fera jamais." />
    </Head>

    <AppLayout>
        <section class="container-editorial pt-editorial pb-12 max-w-3xl">
            <p class="font-sans text-caption uppercase tracking-[0.2em] text-bia-primary mb-4">
                À propos
            </p>
            <h1 class="font-serif text-hero font-medium text-bia-ink leading-tight mb-6">
                Le carnet vivant<br>
                <span class="text-bia-primary">des namurois.</span>
            </h1>
            <p class="font-serif text-h3 text-bia-ink-soft italic leading-snug max-w-reading">
                Pour celles et ceux qui aiment Namur. Pas pour les touristes de passage.
            </p>
        </section>

        <section class="container-editorial py-8 max-w-reading">
            <h2 class="font-serif text-h2 font-medium text-bia-ink mb-4">Pourquoi</h2>
            <p class="prose-bia mb-reading">
                Il y a tout ce qui existe déjà : Visit Namur, l'Office du Tourisme, les pages Facebook
                des commerçants, l'agenda papier de <em class="font-serif italic">Vivre Ici</em>.
                Bia Namur n'est rien de tout ça.
            </p>
            <p class="prose-bia mb-reading">
                C'est un carnet — pas un annuaire. Un compagnon — pas un guide.
                Une voix — pas une plateforme neutre. On choisit, on raconte, on garde un ton.
                Si la sélection ne te ressemble pas, tu prends un autre carnet ; si elle te plaît,
                tu reviens vendredi soir pour le prochain brief.
            </p>
        </section>

        <section class="container-editorial py-8 max-w-reading">
            <h2 class="font-serif text-h2 font-medium text-bia-ink mb-4">Comment ça marche</h2>
            <p class="prose-bia mb-reading">
                Chaque vendredi, on regarde la semaine à venir et on choisit cinq à sept rendez-vous
                dans Namur — un concert intime, une expo discrète, un marché qui revient,
                une dégustation. On rédige le brief, on le relit, on le publie.
            </p>
            <p class="prose-bia mb-reading">
                Les bonnes adresses qui peuplent la carte sentimentale viennent de trois sources :
                notre propre curation, les contributions des Namurois qui suggèrent leurs pépites
                via le formulaire <Link href="/contribuer" class="text-bia-primary underline-offset-4 hover:underline">Contribuer</Link>,
                et les données ouvertes de la Ville (data.namur.be).
                Toutes les contributions sont relues à la main.
            </p>
            <p class="prose-bia mb-reading">
                Une intelligence artificielle nous aide à digérer le volume — résumer,
                classer, repérer les redondances. Elle ne décide pas du ton. Elle ne décide
                pas non plus de ce qui est publié : c'est nous qui validons à la main, à chaque fois.
            </p>
        </section>

        <section class="container-editorial py-8 max-w-reading">
            <h2 class="font-serif text-h2 font-medium text-bia-ink mb-4">Ce qu'on ne fera pas</h2>
            <ul class="space-y-3 prose-bia">
                <li class="border-l-2 border-bia-accent pl-4">
                    <strong class="font-serif text-bia-ink">Pas de pub display.</strong>
                    Pas de bannières, pas de pop-up, pas de tracking publicitaire. Le contenu n'est jamais
                    interrompu par un encart pour vendre des bottes.
                </li>
                <li class="border-l-2 border-bia-accent pl-4">
                    <strong class="font-serif text-bia-ink">Pas de revente de tes données.</strong>
                    Ton email, tes favoris, tes contributions — rien ne quitte Bia Namur pour atterrir
                    chez un courtier ou un partenaire commercial. Jamais.
                </li>
                <li class="border-l-2 border-bia-accent pl-4">
                    <strong class="font-serif text-bia-ink">Pas d'engagement artificiel.</strong>
                    Pas de compteurs « 12 personnes regardent ce lieu », pas de notifications culpabilisantes,
                    pas de FOMO. Tu reviens parce que le contenu te plaît, pas parce qu'on t'a manipulé.
                </li>
                <li class="border-l-2 border-bia-accent pl-4">
                    <strong class="font-serif text-bia-ink">Pas de fiche sans photo.</strong>
                    Une bonne adresse mérite un visuel. Si on n'a pas de photo qui rend justice au lieu,
                    on attend d'en avoir une.
                </li>
            </ul>
        </section>

        <section class="container-editorial py-8 max-w-reading">
            <h2 class="font-serif text-h2 font-medium text-bia-ink mb-4">Modèle économique</h2>
            <p class="prose-bia mb-reading">
                Bia Namur est gratuit au lancement, et le restera pour la lecture du brief
                et la consultation de la carte. À terme, deux sources de revenus, sobres et transparentes :
            </p>
            <ul class="space-y-3 prose-bia mb-reading">
                <li>
                    <strong class="font-serif text-bia-ink">Bia +</strong> — un abonnement optionnel
                    (3 €/mois ou 25 €/an) qui débloque favoris illimités, notifications de proximité,
                    brief personnalisé, mode hors ligne enrichi. Si tu veux nous soutenir,
                    voilà la voie principale.
                </li>
                <li>
                    <strong class="font-serif text-bia-ink">Encarts éditorialisés</strong> — quelques stories
                    sponsorisées par mois, clairement marquées « Présenté par [commerçant] », rédigées
                    avec le même soin que le contenu éditorial. Maximum 5 commerçants par mois,
                    transparence totale.
                </li>
            </ul>
            <p class="prose-bia">
                Quand on activera ces revenus (vraisemblablement en 2026), on publiera ici-même
                les chiffres : combien d'abonnés, combien de revenus, où ça passe.
                Pas de comptes opaques.
            </p>
        </section>

        <section class="container-editorial py-8 max-w-reading">
            <h2 class="font-serif text-h2 font-medium text-bia-ink mb-4">Qui</h2>
            <p class="prose-bia mb-reading">
                Bia Namur est porté par <strong class="font-serif text-bia-ink">Thibaut Marchal</strong>,
                développeur basé à La Bruyère, attaché à Namur depuis toujours.
                Le projet est bénévole et indépendant — pas de financement publicitaire,
                pas de subvention pour le lancement.
            </p>
            <p class="prose-bia mb-reading">
                Pour toute question éditoriale ou technique :
                <a href="mailto:hello@bianamur.be" class="text-bia-primary underline-offset-4 hover:underline">hello@bianamur.be</a>.
                Pour les questions RGPD :
                <a href="mailto:privacy@bianamur.be" class="text-bia-primary underline-offset-4 hover:underline">privacy@bianamur.be</a>.
            </p>
        </section>

        <section class="container-editorial py-editorial border-t border-bia-cream-dk max-w-reading">
            <p class="font-serif italic text-bia-ink-soft text-h3">
                On y va doucement, on prend le temps de bien faire.
                C'est bia, ça suffit.
            </p>
        </section>
    </AppLayout>
</template>
