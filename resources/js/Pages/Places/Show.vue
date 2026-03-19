<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineProps({
    place: {
        type: Object,
        required: true,
    },
    reviews: {
        type: Object,
        required: true,
    },
    syncRuns: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <AuthenticatedLayout>
        <h1 class="text-2xl font-bold mb-4">{{ place.name }}</h1>
        <p class="text-gray-600 mb-6">{{ place.source_url }}</p>

        <h2 class="text-xl font-semibold mb-2">Reviews</h2>
        <div v-if="reviews?.data?.length" class="space-y-2 mb-6">
            <div v-for="review in reviews.data" :key="review.id" class="p-3 border rounded">
                <div class="text-sm font-semibold">{{ review.author_name || 'Anonymous' }}</div>
                <div class="text-sm">{{ review.text }}</div>
            </div>
        </div>
        <div v-else class="text-gray-600 mb-6">No reviews yet.</div>

        <h2 class="text-xl font-semibold mb-2">Sync History</h2>
        <div v-if="syncRuns?.data?.length" class="space-y-2">
            <div v-for="run in syncRuns.data" :key="run.id" class="p-3 border rounded">
                <div class="text-sm font-semibold">Status: {{ run.status }}</div>
                <div class="text-sm text-gray-600">Fetched: {{ run.reviews_fetched }}, Created: {{ run.reviews_created }}, Updated: {{ run.reviews_updated }}</div>
            </div>
        </div>
        <div v-else class="text-gray-600">No sync runs yet.</div>
    </AuthenticatedLayout>
</template>
