<script setup>
import { computed, ref } from 'vue';
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
    photo: null,
});

const descCount = computed(() => (form.description || '').length);
const descLimit = 500;

const photoPreview = ref(null);
const photoInput = ref(null);

const onPhotoChange = (event) => {
    const file = event.target.files?.[0];
    if (! file) {
        form.photo = null;
        photoPreview.value = null;
        return;
    }
    if (file.size > 5 * 1024 * 1024) {
        form.errors.photo = 'La photo dépasse 5 Mo. Compresse-la avant de l\'envoyer.';
        photoInput.value.value = '';
        return;
    }
    form.photo = file;
    form.errors.photo = null;

    const reader = new FileReader();
    reader.onload = (e) => { photoPreview.value = e.target.result; };
    reader.readAsDataURL(file);
};

const removePhoto = () => {
    form.photo = null;
    photoPreview.value = null;
    if (photoInput.value) photoInput.value.value = '';
};

const submit = () => form.post('/contribuer', {
    preserveScroll: true,
    forceFormData: true,    // necessaire pour multipart/form-data avec File
});
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

                <!-- Photo (vivement recommandée) -->
                <div>
                    <label for="photo" class="block text-caption uppercase tracking-widest text-bia-ink-soft mb-2">
                        Une photo
                        <span class="text-bia-primary lowercase tracking-normal not-italic ml-1">— vivement recommandée</span>
                    </label>
                    <p class="text-caption text-bia-ink-soft italic mb-3 max-w-reading">
                        Sans photo, ta suggestion a beaucoup moins de chances d'être publiée — on ne fait pas paraître
                        de fiches sans visuel. Une photo prise au téléphone suffit largement.
                    </p>

                    <div v-if="photoPreview" class="relative rounded-card overflow-hidden border border-bia-cream-dk bg-bia-cream-dk">
                        <img :src="photoPreview" alt="Aperçu de la photo" class="w-full max-h-80 object-cover" />
                        <button
                            type="button"
                            @click="removePhoto"
                            class="absolute top-3 right-3 bg-bia-cream/90 backdrop-blur-sm rounded-pill px-3 py-1.5 text-caption text-bia-ink hover:text-bia-accent transition-colors"
                        >
                            ✕ Retirer
                        </button>
                    </div>

                    <label
                        v-else
                        for="photo"
                        class="flex flex-col items-center justify-center gap-2 rounded-card border-2 border-dashed border-bia-cream-dk bg-white px-6 py-10 cursor-pointer hover:border-bia-primary hover:bg-bia-cream/30 transition-colors"
                    >
                        <svg class="w-8 h-8 text-bia-ink-mute" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Z" />
                        </svg>
                        <span class="text-body text-bia-ink-soft">Cliquer pour ajouter une photo</span>
                        <span class="text-caption text-bia-ink-mute italic">JPG / PNG / WebP, 5 Mo max.</span>
                    </label>

                    <input
                        id="photo"
                        ref="photoInput"
                        type="file"
                        accept="image/jpeg,image/png,image/webp"
                        class="sr-only"
                        @change="onPhotoChange"
                    />
                    <p v-if="form.errors.photo" class="mt-2 text-caption text-bia-accent">{{ form.errors.photo }}</p>
                    <p class="mt-2 text-xs text-bia-ink-mute italic">
                        Pas de photo où on voit des personnes identifiables sans leur accord.
                        Les données EXIF (géoloc, appareil) sont automatiquement supprimées.
                    </p>
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
