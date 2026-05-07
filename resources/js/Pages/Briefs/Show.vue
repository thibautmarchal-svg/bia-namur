<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import EditorialHero from '@/Components/EditorialHero.vue';
import BriefList from '@/Components/BriefList.vue';

const props = defineProps({
    brief: { type: Object, required: true },
});

const items = props.brief.items?.data ?? props.brief.items ?? [];
</script>

<template>
    <Head>
        <title>{{ brief.title }}</title>
        <meta name="description" :content="brief.intro_text" />
    </Head>

    <AppLayout>
        <EditorialHero
            :eyebrow="`Brief ${brief.year}-W${String(brief.week_number).padStart(2, '0')}`"
            :title="brief.title"
        >
            <template #intro>
                <p class="prose-bia text-lg max-w-reading">{{ brief.intro_text }}</p>
            </template>
            <template #actions>
                <Link href="/briefs" class="btn-ghost text-caption">← Tous les briefs</Link>
            </template>
        </EditorialHero>

        <section class="container-editorial py-8 max-w-3xl">
            <BriefList :items="items" />
        </section>
    </AppLayout>
</template>
