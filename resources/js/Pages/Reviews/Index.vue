<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import EmptyState from '@/Components/EmptyState.vue';
import Pagination from '@/Components/Pagination.vue';
import ReviewCard from '@/Components/ReviewCard.vue';
import ReviewFilters from '@/Components/ReviewFilters.vue';
import { router } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    filters: {
        type: Object,
        required: true,
    },
    places: {
        type: [Array, Object],
        required: true,
    },
    reviews: {
        type: Object,
        required: true,
    },
});

const normalizedPlaces = computed(() => Array.isArray(props.places) ? props.places : (props.places?.data ?? []));

function applyFilters(data) {
    router.get('/reviews', data, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function resetFilters() {
    router.get('/reviews', {}, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}
</script>

<template>
    <AppLayout>
        <div class="mb-5">
            <h1 class="text-3xl font-bold text-slate-900">Reviews</h1>
            <p class="mt-1 text-sm text-slate-500">Global feed across all your organizations.</p>
        </div>

        <ReviewFilters
            :filters="filters"
            :places="normalizedPlaces"
            :with-place="true"
            @apply="applyFilters"
            @reset="resetFilters"
        />

        <div v-if="reviews?.data?.length" class="mt-4 space-y-3">
            <ReviewCard v-for="review in reviews.data" :key="review.id" :review="review" />
        </div>
        <EmptyState
            v-else
            title="No reviews found"
            description="Apply different filters or sync organizations to load data."
            class="mt-4"
        />

        <div v-if="reviews?.links?.length" class="mt-4">
            <Pagination :links="reviews.links" />
        </div>
    </AppLayout>
</template>
