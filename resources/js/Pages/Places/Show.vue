<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import RatingBadge from '@/Components/RatingBadge.vue';
import ReviewCard from '@/Components/ReviewCard.vue';
import ReviewFilters from '@/Components/ReviewFilters.vue';
import SyncRunsTable from '@/Components/SyncRunsTable.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
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
    filters: {
        type: Object,
        required: true,
    },
});

const syncing = ref(false);

function applyFilters(data) {
    router.get(`/places/${props.place.id}`, {
        ...data,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function resetFilters() {
    router.get(window.location.pathname, {}, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function syncNow(placeId) {
    syncing.value = true;
    router.post(`/places/${placeId}/sync`, {}, {
        preserveScroll: true,
        onFinish: () => {
            syncing.value = false;
        },
    });
}

function formatDate(value) {
    if (!value) {
        return 'Never';
    }
    return new Date(value).toLocaleString();
}
</script>

<template>
    <AppLayout>
        <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-slate-900">{{ place.name }}</h1>
                    <a :href="place.source_url" target="_blank" class="mt-1 inline-block text-sm font-medium text-cyan-700 hover:text-cyan-900">
                        {{ place.source_url }}
                    </a>
                    <div class="mt-4 flex flex-wrap gap-4 text-sm text-slate-600">
                        <div><span class="font-semibold text-slate-900">{{ place.reviews_count }}</span> reviews</div>
                        <div><span class="font-semibold text-slate-900">{{ formatDate(place.last_synced_at) }}</span> last sync</div>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <RatingBadge :rating="place.rating" />
                    <button type="button" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white"
                            :disabled="syncing" @click="syncNow(place.id)">
                        {{ syncing ? 'Syncing...' : 'Sync now' }}
                    </button>
                    <Link :href="`/places/${place.id}/edit`" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">
                        Edit
                    </Link>
                </div>
            </div>
        </div>

        <div class="mb-6">
            <h2 class="mb-3 text-xl font-semibold text-slate-900">Reviews</h2>
            <ReviewFilters
                :filters="filters"
                :with-place="false"
                @apply="applyFilters"
                @reset="resetFilters"
            />
            <div v-if="reviews?.data?.length" class="mt-4 space-y-3">
                <ReviewCard v-for="review in reviews.data" :key="review.id" :review="review" :organization-name="place.name" />
            </div>
            <div v-else class="mt-4 rounded-xl border border-dashed border-slate-300 bg-white p-6 text-center text-slate-500">
                No reviews found for selected filters.
            </div>
            <div v-if="reviews.links?.length" class="mt-4">
                <Pagination :links="reviews.links" />
            </div>
        </div>

        <div>
            <h2 class="mb-3 text-xl font-semibold text-slate-900">Sync history</h2>
            <SyncRunsTable :rows="syncRuns.data || []" />
            <div v-if="syncRuns.links?.length" class="mt-4">
                <Pagination :links="syncRuns.links" />
            </div>
        </div>
    </AppLayout>
</template>
