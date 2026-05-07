<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    user: { type: Object, required: true },
    stats: { type: Object, required: true },
});

const profileForm = useForm({
    name: props.user.name,
    locale: props.user.locale,
});

const updateProfile = () => {
    profileForm.put('/mon-compte', { preserveScroll: true });
};

// Suppression : confirmation par saisie email
const showDeleteConfirm = ref(false);
const deleteForm = useForm({
    confirm_email: '',
});

const canDelete = computed(() =>
    deleteForm.confirm_email.toLowerCase().trim() === props.user.email.toLowerCase(),
);

const deleteAccount = () => {
    if (! canDelete.value) return;
    if (! confirm('Suppression définitive. Tu es sûr·e ?')) return;
    deleteForm.post('/me/delete');
};

const tierLabel = computed(() => ({
    free: 'Gratuit',
    plus: 'Bia +',
    patron: 'Patron',
}[props.user.subscription_tier] ?? props.user.subscription_tier));
</script>

<template>
    <Head>
        <title>Mon compte</title>
        <meta name="robots" content="noindex" />
    </Head>

    <AppLayout>
        <section class="container-editorial pt-editorial pb-8 max-w-3xl">
            <p class="font-sans text-caption uppercase tracking-[0.2em] text-bia-primary mb-4">
                Mon espace
            </p>
            <h1 class="font-serif text-h1 font-medium text-bia-ink mb-3">
                Mon compte
            </h1>
            <p class="prose-bia max-w-reading">
                Tes informations personnelles, tes données d'activité, et les outils RGPD pour
                exporter ou supprimer ton compte.
            </p>
        </section>

        <section class="container-editorial py-8 max-w-3xl space-y-editorial">
            <div class="rounded-card border border-bia-cream-dk bg-white p-6 sm:p-8">
                <h2 class="font-serif text-h2 font-medium text-bia-ink mb-2">Profil</h2>
                <p class="text-body text-bia-ink-soft mb-6">
                    L'email est immuable (c'est ta clé d'authentification magic link).
                </p>

                <form @submit.prevent="updateProfile" class="space-y-4">
                    <div>
                        <label class="block text-caption font-medium text-bia-ink mb-1.5">Nom</label>
                        <input
                            v-model="profileForm.name"
                            type="text"
                            required
                            maxlength="255"
                            class="w-full rounded-card border border-bia-cream-dk bg-white px-4 py-2.5 text-body text-bia-ink focus:border-bia-primary focus:outline-none transition-colors"
                        />
                        <p v-if="profileForm.errors.name" class="mt-1 text-caption text-bia-accent">
                            {{ profileForm.errors.name }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-caption font-medium text-bia-ink mb-1.5">Email</label>
                        <input
                            :value="user.email"
                            type="email"
                            disabled
                            class="w-full rounded-card border border-bia-cream-dk bg-bia-cream-dk/30 px-4 py-2.5 text-body text-bia-ink-mute cursor-not-allowed"
                        />
                    </div>

                    <div>
                        <label class="block text-caption font-medium text-bia-ink mb-1.5">Langue</label>
                        <select
                            v-model="profileForm.locale"
                            class="rounded-card border border-bia-cream-dk bg-white px-4 py-2.5 text-body text-bia-ink focus:border-bia-primary focus:outline-none"
                        >
                            <option value="fr">Français</option>
                            <option value="en">English</option>
                            <option value="nl">Nederlands</option>
                        </select>
                    </div>

                    <button
                        type="submit"
                        :disabled="profileForm.processing"
                        class="btn-primary"
                    >
                        {{ profileForm.processing ? 'Enregistrement…' : 'Enregistrer' }}
                    </button>
                </form>
            </div>

            <div class="rounded-card border border-bia-cream-dk bg-white p-6 sm:p-8">
                <h2 class="font-serif text-h2 font-medium text-bia-ink mb-2">Activité</h2>
                <p class="text-body text-bia-ink-soft mb-6">
                    Tes traces sur Bia Namur.
                </p>

                <dl class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <dt class="text-caption uppercase tracking-[0.18em] text-bia-ink-mute mb-1">Tier</dt>
                        <dd class="font-serif text-h3 text-bia-ink">{{ tierLabel }}</dd>
                    </div>
                    <div>
                        <dt class="text-caption uppercase tracking-[0.18em] text-bia-ink-mute mb-1">Contributions</dt>
                        <dd class="font-serif text-h3 text-bia-ink">{{ stats.contributions }}</dd>
                    </div>
                    <div>
                        <dt class="text-caption uppercase tracking-[0.18em] text-bia-ink-mute mb-1">Favoris</dt>
                        <dd class="font-serif text-h3 text-bia-ink">{{ stats.favorites }}</dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-card border border-bia-primary/20 bg-bia-primary/5 p-6 sm:p-8">
                <p class="font-sans text-caption uppercase tracking-[0.2em] text-bia-primary mb-3">RGPD</p>
                <h2 class="font-serif text-h3 font-medium text-bia-ink mb-3">Tes données te suivent</h2>
                <p class="text-body text-bia-ink-soft leading-relaxed mb-4">
                    Conformément au RGPD, tu peux exporter toutes tes données personnelles à tout moment
                    dans un fichier JSON unique. Ça inclut ton profil, tes contributions, tes favoris,
                    et tes abonnements aux notifications.
                </p>
                <a
                    href="/me/export"
                    download
                    class="inline-flex items-center gap-2 btn-primary"
                >
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="7 10 12 15 17 10"/>
                        <line x1="12" y1="15" x2="12" y2="3"/>
                    </svg>
                    Télécharger mes données
                </a>
            </div>

            <div class="rounded-card border border-bia-accent/30 bg-white p-6 sm:p-8">
                <p class="font-sans text-caption uppercase tracking-[0.2em] text-bia-accent mb-3">Zone sensible</p>
                <h2 class="font-serif text-h3 font-medium text-bia-ink mb-3">Supprimer mon compte</h2>
                <p class="text-body text-bia-ink-soft leading-relaxed mb-4">
                    La suppression est définitive. Ton profil, tes favoris et tes abonnements
                    aux notifications seront effacés. Les contributions que tu as faites
                    <strong>restent en ligne mais sont anonymisées</strong> (un lieu suggéré qui a
                    été publié continue d'exister sans ton nom).
                </p>

                <button
                    v-if="!showDeleteConfirm"
                    type="button"
                    @click="showDeleteConfirm = true"
                    class="inline-flex items-center justify-center rounded-card px-5 py-3 border border-bia-accent text-bia-accent hover:bg-bia-accent hover:text-white transition-colors font-medium"
                >
                    Je veux supprimer mon compte
                </button>

                <form v-else @submit.prevent="deleteAccount" class="space-y-3">
                    <label class="block text-caption font-medium text-bia-ink">
                        Pour confirmer, saisis ton email&nbsp;:
                        <span class="font-mono text-bia-accent">{{ user.email }}</span>
                    </label>
                    <input
                        v-model="deleteForm.confirm_email"
                        type="email"
                        required
                        autocomplete="off"
                        class="w-full rounded-card border border-bia-accent/50 bg-white px-4 py-2.5 text-body text-bia-ink focus:border-bia-accent focus:outline-none transition-colors"
                        placeholder="Saisis ton email exact"
                    />
                    <p v-if="deleteForm.errors.confirm_email" class="text-caption text-bia-accent">
                        {{ deleteForm.errors.confirm_email }}
                    </p>
                    <div class="flex flex-wrap gap-3 pt-1">
                        <button
                            type="submit"
                            :disabled="!canDelete || deleteForm.processing"
                            :class="[
                                'inline-flex items-center justify-center rounded-card px-5 py-3 font-medium transition-colors',
                                canDelete
                                    ? 'bg-bia-accent text-white hover:bg-bia-accent/90'
                                    : 'bg-bia-cream-dk text-bia-ink-mute cursor-not-allowed',
                            ]"
                        >
                            {{ deleteForm.processing ? 'Suppression…' : 'Supprimer définitivement' }}
                        </button>
                        <button
                            type="button"
                            @click="showDeleteConfirm = false; deleteForm.reset()"
                            class="btn-ghost"
                        >
                            Annuler
                        </button>
                    </div>
                </form>
            </div>
        </section>
    </AppLayout>
</template>
