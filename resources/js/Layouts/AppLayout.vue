<script setup>
import { Link, usePage, router } from '@inertiajs/vue3';
import { computed, ref, watch, onMounted, onBeforeUnmount } from 'vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';

defineProps({
    title: { type: String, default: null },
});

const page = usePage();
const user = computed(() => page.props.auth?.user ?? null);

const navLinks = [
    { href: '/', label: 'Brief' },
    { href: '/lieux', label: 'Lieux' },
    { href: '/stories', label: 'Stories' },
    { href: '/carte', label: 'Carte' },
    { href: '/contribuer', label: 'Contribuer' },
];

const isCurrent = (href) => {
    const path = page.url.split('?')[0].replace(/\/$/, '') || '/';
    if (href === '/') return path === '/' || path.startsWith('/brief');
    return path === href || path.startsWith(href.replace(/s$/, ''));
};

const logout = () => router.post('/logout');

// Drawer mobile
const drawerOpen = ref(false);

const closeDrawer = () => { drawerOpen.value = false; };
const openDrawer = () => { drawerOpen.value = true; };

// Lock scroll quand le drawer est ouvert + ESC pour fermer
watch(drawerOpen, (open) => {
    if (typeof document === 'undefined') return;
    document.body.style.overflow = open ? 'hidden' : '';
});

const onEscape = (e) => {
    if (e.key === 'Escape' && drawerOpen.value) closeDrawer();
};

// Ferme automatiquement le drawer apres une navigation Inertia (clic sur Link)
const removeNavListener = () => {};
onMounted(() => {
    if (typeof document === 'undefined') return;
    document.addEventListener('keyup', onEscape);
    router.on('navigate', closeDrawer);
});
onBeforeUnmount(() => {
    if (typeof document === 'undefined') return;
    document.removeEventListener('keyup', onEscape);
    document.body.style.overflow = '';
});
</script>

<template>
    <div class="min-h-screen flex flex-col">
        <header class="border-b border-bia-cream-dk relative z-30">
            <div class="container-editorial py-4 lg:py-6 flex items-center justify-between gap-3 lg:gap-4">
                <Link
                    href="/"
                    class="inline-flex items-center group shrink-0"
                    aria-label="Bia Namur — accueil"
                >
                    <img src="/logo.svg" alt="Bia Namur" class="h-12 sm:h-14 lg:h-16 w-auto" />
                </Link>

                <nav class="hidden lg:flex items-center gap-1 lg:gap-2" aria-label="Navigation principale">
                    <Link
                        v-for="link in navLinks"
                        :key="link.href"
                        :href="link.href"
                        :class="[
                            'px-3 py-2 text-caption transition-colors rounded-card',
                            isCurrent(link.href)
                                ? 'text-bia-primary font-medium'
                                : 'text-bia-ink-soft hover:text-bia-primary',
                        ]"
                    >
                        {{ link.label }}
                    </Link>
                </nav>

                <div class="hidden lg:flex items-center gap-3 text-caption shrink-0">
                    <Link
                        href="/recherche"
                        class="inline-flex items-center justify-center rounded-pill p-2 text-bia-ink-soft hover:text-bia-primary hover:bg-bia-cream-dk transition-colors"
                        aria-label="Rechercher"
                    >
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <circle cx="11" cy="11" r="7"/>
                            <path d="m21 21-4.3-4.3"/>
                        </svg>
                    </Link>
                    <ThemeToggle />
                    <template v-if="user">
                        <Link
                            href="/mes-favoris"
                            class="inline-flex items-center justify-center rounded-pill p-2 text-bia-ink-soft hover:text-bia-primary hover:bg-bia-cream-dk transition-colors"
                            aria-label="Mes favoris"
                        >
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M12 21s-7.5-4.7-9.6-9.3C1 8.4 3 5 6.4 5c2 0 3.6 1.1 4.6 2.6C12 6.1 13.6 5 15.6 5 19 5 21 8.4 19.6 11.7 17.5 16.3 12 21 12 21z"/>
                            </svg>
                        </Link>
                        <Link href="/mon-compte" class="text-bia-ink-soft hover:text-bia-primary transition-colors">
                            Mon compte
                        </Link>
                        <a v-if="user.is_admin" href="/admin" class="text-bia-ink-soft hover:text-bia-primary transition-colors">
                            Admin
                        </a>
                        <button type="button" @click="logout" class="text-bia-ink-soft hover:text-bia-primary transition-colors">
                            Déconnexion
                        </button>
                    </template>
                    <Link v-else href="/login" class="text-bia-ink-soft hover:text-bia-primary transition-colors">
                        Connexion
                    </Link>
                </div>

                <div class="flex lg:hidden items-center gap-1 shrink-0">
                    <Link
                        href="/recherche"
                        class="inline-flex items-center justify-center rounded-pill p-2 text-bia-ink-soft hover:text-bia-primary hover:bg-bia-cream-dk transition-colors"
                        aria-label="Rechercher"
                    >
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <circle cx="11" cy="11" r="7"/>
                            <path d="m21 21-4.3-4.3"/>
                        </svg>
                    </Link>
                    <ThemeToggle />
                    <button
                        type="button"
                        @click="openDrawer"
                        class="inline-flex items-center justify-center rounded-pill p-2 text-bia-ink-soft hover:text-bia-primary hover:bg-bia-cream-dk transition-colors"
                        aria-label="Ouvrir le menu"
                        aria-haspopup="dialog"
                        :aria-expanded="drawerOpen"
                    >
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                            <line x1="4" y1="7" x2="20" y2="7"/>
                            <line x1="4" y1="12" x2="20" y2="12"/>
                            <line x1="4" y1="17" x2="20" y2="17"/>
                        </svg>
                    </button>
                </div>
            </div>
        </header>

        <Transition name="drawer-fade">
            <div
                v-if="drawerOpen"
                class="fixed inset-0 bg-bia-ink/50 backdrop-blur-sm z-40 lg:hidden"
                @click="closeDrawer"
                aria-hidden="true"
            />
        </Transition>

        <Transition name="drawer-slide">
            <aside
                v-if="drawerOpen"
                class="fixed inset-y-0 right-0 w-[85vw] max-w-sm bg-white z-50 shadow-2xl flex flex-col lg:hidden"
                role="dialog"
                aria-modal="true"
                aria-label="Menu de navigation"
            >
                <div class="flex items-center justify-between px-5 py-4 border-b border-bia-cream-dk">
                    <p class="font-serif text-h3 font-medium text-bia-ink">Menu</p>
                    <button
                        type="button"
                        @click="closeDrawer"
                        class="inline-flex items-center justify-center rounded-pill p-2 text-bia-ink-soft hover:text-bia-primary hover:bg-bia-cream-dk transition-colors"
                        aria-label="Fermer le menu"
                    >
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                            <line x1="6" y1="6" x2="18" y2="18"/>
                            <line x1="18" y1="6" x2="6" y2="18"/>
                        </svg>
                    </button>
                </div>

                <nav class="flex-1 overflow-y-auto px-2 py-4" aria-label="Navigation principale mobile">
                    <ul class="space-y-1">
                        <li v-for="link in navLinks" :key="link.href">
                            <Link
                                :href="link.href"
                                :class="[
                                    'block px-4 py-3 rounded-card font-serif text-lg transition-colors',
                                    isCurrent(link.href)
                                        ? 'bg-bia-primary/10 text-bia-primary font-medium'
                                        : 'text-bia-ink hover:bg-bia-cream-dk hover:text-bia-primary',
                                ]"
                            >
                                {{ link.label }}
                            </Link>
                        </li>
                    </ul>

                    <div v-if="user" class="mt-6 pt-6 border-t border-bia-cream-dk">
                        <p class="px-4 mb-2 text-caption uppercase tracking-[0.18em] text-bia-ink-mute">
                            Mon espace
                        </p>
                        <ul class="space-y-1">
                            <li>
                                <Link
                                    href="/mes-favoris"
                                    class="flex items-center gap-3 px-4 py-3 rounded-card text-bia-ink hover:bg-bia-cream-dk hover:text-bia-primary transition-colors"
                                >
                                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M12 21s-7.5-4.7-9.6-9.3C1 8.4 3 5 6.4 5c2 0 3.6 1.1 4.6 2.6C12 6.1 13.6 5 15.6 5 19 5 21 8.4 19.6 11.7 17.5 16.3 12 21 12 21z"/>
                                    </svg>
                                    Mes favoris
                                </Link>
                            </li>
                            <li>
                                <Link
                                    href="/mon-compte"
                                    class="flex items-center gap-3 px-4 py-3 rounded-card text-bia-ink hover:bg-bia-cream-dk hover:text-bia-primary transition-colors"
                                >
                                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                        <circle cx="12" cy="7" r="4"/>
                                    </svg>
                                    Mon compte
                                </Link>
                            </li>
                            <li v-if="user.is_admin">
                                <a
                                    href="/admin"
                                    class="flex items-center gap-3 px-4 py-3 rounded-card text-bia-ink hover:bg-bia-cream-dk hover:text-bia-primary transition-colors"
                                >
                                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                                    </svg>
                                    Admin
                                </a>
                            </li>
                        </ul>
                    </div>
                </nav>

                <div class="px-2 py-4 border-t border-bia-cream-dk">
                    <template v-if="user">
                        <p class="px-4 pb-2 text-caption text-bia-ink-mute truncate">
                            Connecté en tant que <span class="text-bia-ink">{{ user.name }}</span>
                        </p>
                        <button
                            type="button"
                            @click="logout"
                            class="w-full text-left flex items-center gap-3 px-4 py-3 rounded-card text-bia-ink-soft hover:bg-bia-cream-dk hover:text-bia-accent transition-colors"
                        >
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                                <polyline points="16 17 21 12 16 7"/>
                                <line x1="21" y1="12" x2="9" y2="12"/>
                            </svg>
                            Déconnexion
                        </button>
                    </template>
                    <Link
                        v-else
                        href="/login"
                        class="flex items-center gap-3 px-4 py-3 rounded-card bg-bia-primary text-bia-cream font-medium"
                    >
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
                            <polyline points="10 17 15 12 10 7"/>
                            <line x1="15" y1="12" x2="3" y2="12"/>
                        </svg>
                        Connexion
                    </Link>
                </div>
            </aside>
        </Transition>

        <main class="flex-1">
            <slot />
        </main>

        <footer class="border-t border-bia-cream-dk bg-bia-cream-dk/40 mt-editorial">
            <div class="container-editorial py-10 space-y-5 text-caption text-bia-ink-mute">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                    <p class="font-serif italic text-bia-ink-soft">
                        Le carnet vivant des namurois.
                    </p>
                    <p>© {{ new Date().getFullYear() }} Bia Namur</p>
                </div>
                <nav class="flex flex-wrap gap-x-5 gap-y-2" aria-label="Liens éditoriaux">
                    <Link href="/a-propos" class="hover:text-bia-primary transition-colors">À propos</Link>
                    <Link href="/wallon" class="hover:text-bia-primary transition-colors">Wallon namurois</Link>
                    <Link href="/contribuer" class="hover:text-bia-primary transition-colors">Contribuer un lieu</Link>
                    <Link href="/mentions-legales" class="hover:text-bia-primary transition-colors">Mentions légales</Link>
                    <Link href="/cgu" class="hover:text-bia-primary transition-colors">CGU</Link>
                    <Link href="/confidentialite" class="hover:text-bia-primary transition-colors">Confidentialité</Link>
                </nav>
                <p class="text-xs leading-relaxed">
                    Données issues de la plateforme OpenData de la Ville de Namur
                    (<a href="https://data.namur.be" class="underline hover:text-bia-primary" rel="noopener noreferrer" target="_blank">data.namur.be</a>),
                    mises à disposition sous licence
                    <a href="https://creativecommons.org/licenses/by/4.0/" class="underline hover:text-bia-primary" rel="noopener noreferrer" target="_blank">CC BY 4.0</a>.
                </p>
            </div>
        </footer>
    </div>
</template>

<style scoped>
.drawer-fade-enter-active,
.drawer-fade-leave-active {
    transition: opacity 200ms ease;
}
.drawer-fade-enter-from,
.drawer-fade-leave-to {
    opacity: 0;
}

.drawer-slide-enter-active,
.drawer-slide-leave-active {
    transition: transform 250ms cubic-bezier(0.4, 0, 0.2, 1);
}
.drawer-slide-enter-from,
.drawer-slide-leave-to {
    transform: translateX(100%);
}
</style>
