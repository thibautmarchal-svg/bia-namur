<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    title: { type: String, default: null },
});
</script>

<template>
    <div class="min-h-screen flex flex-col">
        <header class="border-b border-bia-cream-dk bg-bia-cream/80 backdrop-blur-sm sticky top-0 z-30">
            <div class="container-editorial flex items-center justify-between py-4">
                <Link
                    href="/"
                    class="flex items-center gap-2 group"
                    aria-label="Bia Namur — accueil"
                >
                    <img src="/logo.svg" alt="" class="h-8 w-8" />
                    <span class="font-serif text-h3 font-medium text-bia-ink">Bia Namur</span>
                </Link>
                <nav aria-label="Navigation principale" class="hidden sm:flex items-center gap-1">
                    <Link href="/" class="btn-ghost text-caption">Accueil</Link>
                    <span class="text-caption text-bia-ink-mute italic px-3">— Carte, stories à venir —</span>
                </nav>
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
