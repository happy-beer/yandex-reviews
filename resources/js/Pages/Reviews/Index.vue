<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineProps({
    filters: {
        type: Object,
        required: true,
    },
    places: {
        type: Array,
        required: true,
    },
    reviews: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <AuthenticatedLayout>
        <h1 class="text-2xl font-bold mb-4">Reviews</h1>
        <div v-if="reviews?.data?.length" class="space-y-2">
            <div v-for="review in reviews.data" :key="review.id" class="p-3 border rounded">
                <div class="text-sm font-semibold">
                    {{ review.author_name || 'Anonymous' }} - {{ review.rating }}/5
                </div>
                <div class="text-sm">{{ review.text }}</div>
            </div>
        </div>
        <div v-else class="text-gray-600">No reviews found.</div>
    </AuthenticatedLayout>
</template>
