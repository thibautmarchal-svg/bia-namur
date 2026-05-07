import { ref, watchEffect } from 'vue';

/**
 * Mode sombre Bia Namur — palette repensée (cf. agent ux-ui-namur §"Mode sombre",
 * pas une simple inversion).
 *
 * Strategie de resolution :
 *   1. localStorage 'bia-theme' = 'light' | 'dark' | 'system' (defaut : 'system')
 *   2. Si 'system' : on suit prefers-color-scheme du navigateur (ecoute en live)
 *   3. Applique la classe 'dark' sur <html> pour activer Tailwind darkMode='class'
 *
 * Persistance : choix utilisateur memorise dans localStorage. La preference
 * systeme bascule en live si l'OS change de mode (matchMedia listener).
 */

const STORAGE_KEY = 'bia-theme';
const VALID_PREFS = ['light', 'dark', 'system'];

const preference = ref('system');
const isDark = ref(false);

const readStoredPreference = () => {
    if (typeof window === 'undefined') return 'system';
    const stored = localStorage.getItem(STORAGE_KEY);
    return VALID_PREFS.includes(stored) ? stored : 'system';
};

const systemPrefersDark = () => {
    if (typeof window === 'undefined') return false;
    return window.matchMedia('(prefers-color-scheme: dark)').matches;
};

const applyTheme = () => {
    const wantsDark = preference.value === 'dark'
        || (preference.value === 'system' && systemPrefersDark());

    isDark.value = wantsDark;

    if (typeof document !== 'undefined') {
        document.documentElement.classList.toggle('dark', wantsDark);
        document.documentElement.style.colorScheme = wantsDark ? 'dark' : 'light';
    }
};

let systemListener = null;
const setupSystemListener = () => {
    if (typeof window === 'undefined' || systemListener) return;
    const mq = window.matchMedia('(prefers-color-scheme: dark)');
    systemListener = () => {
        if (preference.value === 'system') applyTheme();
    };
    mq.addEventListener('change', systemListener);
};

export function useDarkMode() {
    if (typeof window !== 'undefined') {
        preference.value = readStoredPreference();
        applyTheme();
        setupSystemListener();
    }

    watchEffect(() => {
        if (typeof window === 'undefined') return;
        if (preference.value === 'system') {
            localStorage.removeItem(STORAGE_KEY);
        } else {
            localStorage.setItem(STORAGE_KEY, preference.value);
        }
        applyTheme();
    });

    const setTheme = (next) => {
        if (! VALID_PREFS.includes(next)) return;
        preference.value = next;
    };

    const cycleTheme = () => {
        const order = ['light', 'dark', 'system'];
        const idx = order.indexOf(preference.value);
        preference.value = order[(idx + 1) % order.length];
    };

    return {
        preference,
        isDark,
        setTheme,
        cycleTheme,
    };
}

/** Pour appel hors composant (ex: dans bootstrap) — applique sans creer de watch. */
export function applyStoredTheme() {
    if (typeof window === 'undefined') return;
    preference.value = readStoredPreference();
    applyTheme();
    setupSystemListener();
}
