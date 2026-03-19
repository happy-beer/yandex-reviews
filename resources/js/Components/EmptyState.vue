<script setup>
defineProps({
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <div class="rounded-xl border border-dashed border-slate-300 bg-white p-8 text-center">
        <h3 class="text-lg font-semibold text-slate-800">{{ title }}</h3>
        <p v-if="description" class="mt-2 text-sm text-slate-500">{{ description }}</p>
        <slot />
    </div>
</template>
