<script setup>
import { ref } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const page = usePage();

const form = useForm({
    email: '',
});

const submit = () => {
    form.post('/auth/magic-link', {
        preserveScroll: true,
        onSuccess: () => form.reset('email'),
    });
};
</script>

<template>
    <Head>
        <title>Se connecter</title>
    </Head>

    <AppLayout>
        <section class="container-editorial pt-editorial pb-editorial max-w-2xl">
            <p class="font-sans text-caption uppercase tracking-[0.2em] text-bia-primary mb-6">
                Se connecter
            </p>
            <h1 class="font-serif text-h1 font-medium text-bia-ink mb-6">
                On t'envoie un lien<br>
                <span class="text-bia-primary">par email.</span>
            </h1>
            <p class="prose-bia mb-10">
                Pas de mot de passe à retenir. Mets ton adresse, on te poste un lien
                qui expire dans 15 minutes. Tu cliques, tu es chez toi.
            </p>

            <div
                v-if="page.props.flash?.message"
                :class="[
                    'rounded-card border px-5 py-4 mb-8 text-body',
                    page.props.flash.type === 'success'
                        ? 'border-bia-primary/30 bg-bia-primary/5 text-bia-ink'
                        : 'border-bia-accent/30 bg-bia-accent/5 text-bia-accent',
                ]"
                role="status"
            >
                {{ page.props.flash.message }}
            </div>

            <form @submit.prevent="submit" novalidate class="space-y-6">
                <div>
                    <label for="email" class="block text-caption uppercase tracking-widest text-bia-ink-soft mb-2">
                        Ton email
                    </label>
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        autocomplete="email"
                        required
                        placeholder="prenom@exemple.be"
                        class="w-full rounded-card border border-bia-cream-dk bg-white px-4 py-3 text-body text-bia-ink placeholder:text-bia-ink-mute focus:border-bia-primary focus:ring-2 focus:ring-bia-primary/30 focus:outline-none"
                    />
                    <p v-if="form.errors.email" class="mt-2 text-caption text-bia-accent">
                        {{ form.errors.email }}
                    </p>
                </div>
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="btn-primary w-full sm:w-auto disabled:opacity-60 disabled:cursor-not-allowed"
                >
                    {{ form.processing ? 'On envoie…' : "M'envoyer le lien" }}
                </button>
            </form>

            <p class="mt-12 text-caption text-bia-ink-mute leading-relaxed">
                En te connectant, tu acceptes nos
                <a href="#" class="underline hover:text-bia-primary">conditions d'utilisation</a>
                et notre
                <a href="#" class="underline hover:text-bia-primary">politique de confidentialité</a>.
                On ne partage tes données avec personne.
            </p>
        </section>
    </AppLayout>
</template>
