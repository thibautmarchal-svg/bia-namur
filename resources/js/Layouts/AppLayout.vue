<script setup>
import { Link, usePage, router } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({
    title: { type: String, default: null },
});

const page = usePage();
const user = computed(() => page.props.auth?.user ?? null);

const logout = () => router.post('/logout');
</script>

<template>
    <div class="min-h-screen flex flex-col">
        <header class="border-b border-bia-cream-dk">
            <div class="container-editorial py-6 flex items-center justify-between gap-4">
                <Link
                    href="/"
                    class="inline-flex items-center gap-3 group"
                    aria-label="Bia Namur — accueil"
                >
                    <img src="/logo.svg" alt="" class="h-9 w-9" />
                    <span class="font-serif text-h3 font-medium text-bia-ink">Bia Namur</span>
                </Link>

                <nav v-if="user" class="flex items-center gap-3 text-caption">
                    <a
                        v-if="user.is_admin"
                        href="/admin"
                        class="text-bia-ink-soft hover:text-bia-primary transition-colors"
                    >
                        Admin
                    </a>
                    <span class="text-bia-ink-mute hidden sm:inline">·</span>
                    <span class="text-bia-ink-soft hidden sm:inline">{{ user.name }}</span>
                    <button
                        type="button"
                        @click="logout"
                        class="text-bia-ink-soft hover:text-bia-primary transition-colors underline-offset-4 hover:underline"
                    >
                        Se déconnecter
                    </button>
                </nav>

                <Link
                    v-else
                    href="/login"
                    class="text-caption text-bia-ink-soft hover:text-bia-primary transition-colors"
                >
                    Se connecter
                </Link>
            </div>
        </header>

        <main class="flex-1">
            <slot />
        </main>

        <footer class="border-t border-bia-cream-dk bg-bia-cream-dk/40 mt-editorial">
            <div class="container-editorial py-10 space-y-4 text-caption text-bia-ink-mute">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                    <p class="font-serif italic text-bia-ink-soft">
                        Le carnet vivant des namurois.
                    </p>
                    <p>© {{ new Date().getFullYear() }} Bia Namur</p>
                </div>
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
