<script setup>
import { onMounted, onUnmounted, watch } from 'vue';

const props = defineProps({
    schema: { type: Object, required: true },
    id: { type: String, default: 'jsonld-current' },
});

let scriptEl = null;

const inject = (data) => {
    if (typeof document === 'undefined') return;
    if (scriptEl) {
        scriptEl.text = JSON.stringify(data);
        return;
    }
    scriptEl = document.createElement('script');
    scriptEl.type = 'application/ld+json';
    scriptEl.id = props.id;
    scriptEl.text = JSON.stringify(data);
    document.head.appendChild(scriptEl);
};

const remove = () => {
    if (scriptEl) {
        scriptEl.remove();
        scriptEl = null;
    }
};

onMounted(() => inject(props.schema));
onUnmounted(remove);

watch(() => props.schema, (next) => inject(next), { deep: true });
</script>

<template><span aria-hidden="true" hidden /></template>
