<script setup>
import { Link, usePage, router } from '@inertiajs/vue3';
import { computed } from 'vue';
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
</script>

<template>
    <div class="min-h-screen flex flex-col">
        <header class="border-b border-bia-cream-dk">
            <div class="container-editorial py-5 sm:py-6 flex items-center justify-between gap-4">
                <Link
                    href="/"
                    class="inline-flex items-center gap-3 group shrink-0"
                    aria-label="Bia Namur — accueil"
                >
                    <img src="/logo.svg" alt="" class="h-9 w-9" />
                    <span class="font-serif text-h3 font-medium text-bia-ink hidden sm:inline">Bia Namur</span>
                </Link>

                <nav class="flex items-center gap-1 sm:gap-2" aria-label="Navigation principale">
                    <Link
                        v-for="link in navLinks"
                        :key="link.href"
                        :href="link.href"
                        :class="[
                            'px-2 sm:px-3 py-2 text-caption transition-colors rounded-card',
                            isCurrent(link.href)
                                ? 'text-bia-primary font-medium'
                                : 'text-bia-ink-soft hover:text-bia-primary',
                        ]"
                    >
                        {{ link.label }}
                    </Link>
                </nav>

                <div class="flex items-center gap-2 sm:gap-3 text-caption shrink-0">
                    <Link
                        href="/recherche"
                        class="inline-flex items-center justify-center rounded-pill p-2 text-bia-ink-soft hover:text-bia-primary hover:bg-bia-cream-dk transition-colors"
                        aria-label="Rechercher"
                        title="Rechercher"
                    >
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <circle cx="11" cy="11" r="7"/>
                            <path d="m21 21-4.3-4.3"/>
                        </svg>
                    </Link>
                    <ThemeToggle />
                    <template v-if="user">
                        <a
                            v-if="user.is_admin"
                            href="/admin"
                            class="hidden sm:inline text-bia-ink-soft hover:text-bia-primary transition-colors"
                        >
                            Admin
                        </a>
                        <button
                            type="button"
                            @click="logout"
                            class="text-bia-ink-soft hover:text-bia-primary transition-colors"
                            :title="user.name"
                        >
                            Déconnexion
                        </button>
                    </template>
                    <Link
                        v-else
                        href="/login"
                        class="text-bia-ink-soft hover:text-bia-primary transition-colors"
                    >
                        Connexion
                    </Link>
                </div>
            </div>
        </header>

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
