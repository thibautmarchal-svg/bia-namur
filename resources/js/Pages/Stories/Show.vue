<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StoryArticle from '@/Components/StoryArticle.vue';

const props = defineProps({
    story: { type: Object, required: true },
});

const data = props.story.data ?? props.story;
</script>

<template>
    <Head>
        <title>{{ data.title }}</title>
        <meta name="description" :content="data.excerpt" />
    </Head>

    <AppLayout>
        <StoryArticle :story="data" />
    </AppLayout>
</template>
