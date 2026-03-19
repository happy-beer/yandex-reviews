<script setup>
import { computed } from 'vue';

const props = defineProps({
    status: { type: String, required: true },
});

const klass = computed(() => {
    if (props.status === 'success') {
        return 'bg-emerald-100 text-emerald-700';
    }
    if (props.status === 'failed') {
        return 'bg-rose-100 text-rose-700';
    }
    return 'bg-amber-100 text-amber-700';
});
</script>

<template>
    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold capitalize" :class="klass">
        {{ status }}
    </span>
</template>
