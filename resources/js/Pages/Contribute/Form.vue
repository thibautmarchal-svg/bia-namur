<script setup>
import { computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
    types: { type: Array, required: true },
});

const form = useForm({
    website_url: '',     // honeypot — doit rester vide
    name: '',
    type: '',
    description: '',
    address: '',
    neighborhood: '',
    why: '',
    contributor_email: '',
    contributor_name: '',
});

const descCount = computed(() => (form.description || '').length);
const descLimit = 500;

const submit = () => form.post('/contribuer', { preserveScroll: true });
</script>

<template>
    <Head>
        <title>Contribuer un lieu</title>
        <meta name="description" content="Suggère un lieu pour Bia Namur. Bistrot, librairie, vue inattendue — partage tes pépites avec les autres namurois." />
    </Head>

    <AppLayout>
        <section class="container-editorial pt-editorial pb-8 max-w-2xl">
            <p class="font-sans text-caption uppercase tracking-[0.2em] text-bia-primary mb-4">
                Contribuer
            </p>
            <h1 class="font-serif text-h1 font-medium text-bia-ink mb-3">
                Une bonne adresse à partager ?
            </h1>
            <p class="prose-bia max-w-reading">
                Une terrasse magique au matin, une boulangerie de quartier, un banc avec une vue
                qu'on ne soupçonne pas. Suggère ton lieu — on le relit, et s'il est dans le ton
                de Bia, il rejoint la carte.
            </p>
        </section>

        <section class="container-editorial pb-editorial max-w-2xl">
            <form @submit.prevent="submit" novalidate class="space-y-6">
                <!-- Honeypot anti-bot : reste invisible aux humains, doit rester vide -->
                <div aria-hidden="true" class="absolute left-[-9999px] w-1 h-1 overflow-hidden">
                    <label for="website_url">Site (laisser vide)</label>
                    <input
                        id="website_url"
                        v-model="form.website_url"
                        type="text"
                        tabindex="-1"
                        autocomplete="off"
                    />
                </div>

                <!-- Nom du lieu -->
                <div>
                    <label for="name" class="block text-caption uppercase tracking-widest text-bia-ink-soft mb-2">
                        Nom du lieu *
                    </label>
                    <input
                        id="name"
                        v-model="form.name"
                        type="text"
                        required
                        maxlength="120"
                        placeholder="Le café du coin, la librairie X…"
                        class="w-full rounded-card border border-bia-cream-dk bg-white px-4 py-3 text-body text-bia-ink focus:border-bia-primary focus:ring-2 focus:ring-bia-primary/30 focus:outline-none"
                    />
                    <p v-if="form.errors.name" class="mt-2 text-caption text-bia-accent">{{ form.errors.name }}</p>
                </div>

                <!-- Type -->
                <div>
                    <label for="type" class="block text-caption uppercase tracking-widest text-bia-ink-soft mb-2">
                        Type *
                    </label>
                    <select
                        id="type"
                        v-model="form.type"
                        required
                        class="w-full rounded-card border border-bia-cream-dk bg-white px-4 py-3 text-body text-bia-ink focus:border-bia-primary focus:ring-2 focus:ring-bia-primary/30 focus:outline-none"
                    >
                        <option value="" disabled>Choisis un type</option>
                        <option v-for="t in types" :key="t.value" :value="t.value">{{ t.label }}</option>
                    </select>
                    <p v-if="form.errors.type" class="mt-2 text-caption text-bia-accent">{{ form.errors.type }}</p>
                </div>

                <!-- Description -->
                <div>
                    <label for="description" class="block text-caption uppercase tracking-widest text-bia-ink-soft mb-2">
                        Description *
                    </label>
                    <textarea
                        id="description"
                        v-model="form.description"
                        rows="5"
                        required
                        minlength="30"
                        :maxlength="descLimit"
                        placeholder="Qu'est-ce qui rend ce lieu spécifique ? L'ambiance, la vue, l'horaire, le moment de la journée…"
                        class="w-full rounded-card border border-bia-cream-dk bg-white px-4 py-3 text-body text-bia-ink focus:border-bia-primary focus:ring-2 focus:ring-bia-primary/30 focus:outline-none"
                    />
                    <div class="mt-2 flex items-center justify-between text-caption">
                        <p v-if="form.errors.description" class="text-bia-accent">{{ form.errors.description }}</p>
                        <p :class="descCount >= descLimit - 50 ? 'text-bia-accent' : 'text-bia-ink-mute'" class="ml-auto">
                            {{ descCount }} / {{ descLimit }}
                        </p>
                    </div>
                </div>

                <!-- Adresse / quartier -->
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label for="address" class="block text-caption uppercase tracking-widest text-bia-ink-soft mb-2">
                            Adresse
                        </label>
                        <input
                            id="address"
                            v-model="form.address"
                            type="text"
                            maxlength="200"
                            placeholder="Rue, n°, code postal"
                            class="w-full rounded-card border border-bia-cream-dk bg-white px-4 py-3 text-body text-bia-ink focus:border-bia-primary focus:ring-2 focus:ring-bia-primary/30 focus:outline-none"
                        />
                    </div>
                    <div>
                        <label for="neighborhood" class="block text-caption uppercase tracking-widest text-bia-ink-soft mb-2">
                            Quartier
                        </label>
                        <input
                            id="neighborhood"
                            v-model="form.neighborhood"
                            type="text"
                            maxlength="80"
                            placeholder="Centre, Jambes, Bouge…"
                            class="w-full rounded-card border border-bia-cream-dk bg-white px-4 py-3 text-body text-bia-ink focus:border-bia-primary focus:ring-2 focus:ring-bia-primary/30 focus:outline-none"
                        />
                    </div>
                </div>

                <!-- Contact (optionnel) -->
                <details class="group rounded-card border border-bia-cream-dk bg-bia-cream-dk/20 p-5">
                    <summary class="cursor-pointer font-serif text-bia-ink-soft hover:text-bia-primary transition-colors">
                        Tu veux qu'on te dise ce qu'on en a fait ? <span class="text-caption text-bia-ink-mute italic ml-1">(facultatif)</span>
                    </summary>
                    <div class="mt-4 grid sm:grid-cols-2 gap-4">
                        <div>
                            <label for="contributor_name" class="block text-caption uppercase tracking-widest text-bia-ink-soft mb-2">
                                Ton prénom
                            </label>
                            <input
                                id="contributor_name"
                                v-model="form.contributor_name"
                                type="text"
                                maxlength="80"
                                class="w-full rounded-card border border-bia-cream-dk bg-white px-4 py-3 text-body text-bia-ink focus:border-bia-primary focus:ring-2 focus:ring-bia-primary/30 focus:outline-none"
                            />
                        </div>
                        <div>
                            <label for="contributor_email" class="block text-caption uppercase tracking-widest text-bia-ink-soft mb-2">
                                Ton email
                            </label>
                            <input
                                id="contributor_email"
                                v-model="form.contributor_email"
                                type="email"
                                maxlength="255"
                                autocomplete="email"
                                class="w-full rounded-card border border-bia-cream-dk bg-white px-4 py-3 text-body text-bia-ink focus:border-bia-primary focus:ring-2 focus:ring-bia-primary/30 focus:outline-none"
                            />
                            <p class="mt-1 text-xs text-bia-ink-mute italic">
                                Utilisé uniquement pour te tenir au courant. Jamais partagé.
                            </p>
                        </div>
                    </div>
                </details>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="btn-primary w-full sm:w-auto disabled:opacity-60 disabled:cursor-not-allowed"
                >
                    {{ form.processing ? 'Envoi…' : 'Envoyer ma suggestion' }}
                </button>

                <p class="text-caption text-bia-ink-mute leading-relaxed">
                    En envoyant, tu cèdes à Bia Namur le droit de publier ta suggestion (sous une forme
                    relue et adaptée). On la modère sous 1 à 7 jours. Pas de pub déguisée, sois sincère.
                </p>
            </form>
        </section>
    </AppLayout>
</template>
