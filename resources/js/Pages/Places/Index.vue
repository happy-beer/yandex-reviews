<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineProps({
    places: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <AuthenticatedLayout>
        <h1 class="text-2xl font-bold mb-4">Organizations</h1>
        <div v-if="places?.data?.length" class="space-y-2">
            <div v-for="place in places.data" :key="place.id" class="p-3 border rounded">
                <div class="font-semibold">{{ place.name }}</div>
                <div class="text-sm text-gray-600">{{ place.source_url }}</div>
            </div>
        </div>
        <div v-else class="text-gray-600">No organizations yet.</div>
    </AuthenticatedLayout>
</template>
