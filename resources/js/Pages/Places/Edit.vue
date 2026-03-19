<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineProps({
    place: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <AuthenticatedLayout>
        <h1 class="text-2xl font-bold mb-4">Edit Organization</h1>
        <p class="text-gray-600">{{ place.name }}</p>
    </AuthenticatedLayout>
</template>
