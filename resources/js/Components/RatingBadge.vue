<script setup>
defineProps({
    rating: { type: [String, Number], default: null },
});
</script>

<template>
    <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800">
        {{ rating ?? 'n/a' }} ★
    </span>
</template>
