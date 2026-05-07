<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { usePushNotifications } from '@/composables/usePushNotifications';

const props = defineProps({
    variant: {
        type: String,
        default: 'card', // 'card' (boite editoriale) | 'inline' (bouton compact)
        validator: (v) => ['card', 'inline'].includes(v),
    },
});

const page = usePage();
const vapidKey = computed(() => page.props.pushVapidPublicKey ?? null);

const { status, busy, error, canSubscribe, isSubscribed, subscribe, unsubscribe } =
    usePushNotifications(vapidKey.value);

const onToggle = async () => {
    if (isSubscribed.value) {
        await unsubscribe();
    } else if (canSubscribe.value) {
        await subscribe();
    }
};

const buttonLabel = computed(() => {
    if (busy.value) return 'Patiente…';
    if (isSubscribed.value) return 'Désactiver les notifications';
    if (status.value === 'denied') return 'Notifications refusées';
    if (status.value === 'unsupported') return 'Navigateur non compatible';
    return 'Activer les notifications';
});

const isDisabled = computed(() =>
    busy.value || status.value === 'denied' || status.value === 'unsupported',
);
</script>

<template>
    <div v-if="!vapidKey" class="hidden" aria-hidden="true" />

    <div v-else-if="variant === 'card'" class="rounded-card border border-bia-cream-dk bg-white p-5 sm:p-6">
        <p class="font-sans text-caption uppercase tracking-[0.2em] text-bia-primary mb-2">
            Notifications
        </p>
        <h3 class="font-serif text-h3 font-medium text-bia-ink mb-2">
            <template v-if="isSubscribed">Tu reçois le brief en avant-première.</template>
            <template v-else>Reçois le brief dès qu'il sort.</template>
        </h3>
        <p class="text-body text-bia-ink-soft leading-relaxed mb-4">
            <template v-if="isSubscribed">
                Une notif chaque vendredi matin quand le brief de la semaine est en ligne.
                Tu peux désactiver à tout moment.
            </template>
            <template v-else-if="status === 'denied'">
                Tu as refusé les notifications dans ton navigateur. Pour les activer,
                ouvre les paramètres du site dans la barre d'adresse.
            </template>
            <template v-else-if="status === 'unsupported'">
                Ton navigateur ne supporte pas les notifications push.
                Les principaux (Chrome, Firefox, Edge, Safari iOS 16.4+) le font.
            </template>
            <template v-else>
                Une notif chaque vendredi quand le nouveau brief est en ligne. Pas de spam,
                pas de pub, pas de trace publicitaire — c'est un opt-in explicite que tu
                peux retirer en un clic.
            </template>
        </p>
        <button
            type="button"
            @click="onToggle"
            :disabled="isDisabled"
            :class="[
                'inline-flex items-center gap-2 rounded-pill px-5 py-2.5 text-caption font-medium transition-colors',
                isSubscribed
                    ? 'bg-white border border-bia-primary text-bia-primary hover:bg-bia-primary/5'
                    : 'bg-bia-primary text-bia-cream hover:bg-bia-primary-dk',
                isDisabled && 'opacity-50 cursor-not-allowed',
            ]"
        >
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/>
                <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>
            </svg>
            {{ buttonLabel }}
        </button>
        <p v-if="error" class="mt-3 text-caption text-bia-accent">
            Une erreur est survenue : {{ error }}
        </p>
    </div>

    <button
        v-else
        type="button"
        @click="onToggle"
        :disabled="isDisabled"
        :class="[
            'inline-flex items-center gap-2 rounded-pill px-4 py-2 text-caption transition-colors',
            isSubscribed
                ? 'bg-white border border-bia-primary text-bia-primary'
                : 'border border-bia-cream-dk text-bia-ink-soft hover:text-bia-primary hover:border-bia-primary',
            isDisabled && 'opacity-50 cursor-not-allowed',
        ]"
    >
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/>
            <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>
        </svg>
        <span>{{ buttonLabel }}</span>
    </button>
</template>
