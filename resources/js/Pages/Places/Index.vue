<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import RatingBadge from '@/Components/RatingBadge.vue';
import SyncStatusBadge from '@/Components/SyncStatusBadge.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    places: {
        type: Object,
        required: true,
    },
});

const syncingPlaceId = ref(null);
const deletingPlaceId = ref(null);

function syncPlace(placeId) {
    syncingPlaceId.value = placeId;
    router.post(`/places/${placeId}/sync`, {}, {
        preserveScroll: true,
        onFinish: () => {
            syncingPlaceId.value = null;
        },
    });
}

function deletePlace(placeId, name) {
    if (!window.confirm(`Delete organization "${name}"?`)) {
        return;
    }

    deletingPlaceId.value = placeId;
    router.delete(`/places/${placeId}`, {
        preserveScroll: true,
        onFinish: () => {
            deletingPlaceId.value = null;
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
        <div class="mb-5 flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-slate-900">Organizations</h1>
                <p class="mt-1 text-sm text-slate-500">Manage places and trigger manual sync.</p>
            </div>
            <Link href="/places/create" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">
                Add place
            </Link>
        </div>

        <div v-if="places?.data?.length" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-slate-600">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Name</th>
                        <th class="px-4 py-3 font-semibold">Rating</th>
                        <th class="px-4 py-3 font-semibold">Reviews</th>
                        <th class="px-4 py-3 font-semibold">Last sync</th>
                        <th class="px-4 py-3 font-semibold">Status</th>
                        <th class="px-4 py-3 font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="place in places.data" :key="place.id" class="border-t border-slate-100">
                        <td class="px-4 py-3">
                            <p class="font-semibold text-slate-900">{{ place.name }}</p>
                            <p class="text-xs text-slate-500">{{ place.source_url }}</p>
                        </td>
                        <td class="px-4 py-3"><RatingBadge :rating="place.rating" /></td>
                        <td class="px-4 py-3">{{ place.reviews_count }}</td>
                        <td class="px-4 py-3">{{ formatDate(place.last_synced_at) }}</td>
                        <td class="px-4 py-3">
                            <SyncStatusBadge :status="place.is_active ? 'success' : 'pending'" />
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-2">
                                <Link :href="`/places/${place.id}`" class="rounded border border-slate-300 px-2 py-1 text-xs">Open</Link>
                                <Link :href="`/places/${place.id}/edit`" class="rounded border border-slate-300 px-2 py-1 text-xs">Edit</Link>
                                <button type="button" class="rounded border border-slate-300 px-2 py-1 text-xs"
                                        :disabled="syncingPlaceId === place.id"
                                        @click="syncPlace(place.id)">
                                    {{ syncingPlaceId === place.id ? 'Syncing...' : 'Sync' }}
                                </button>
                                <button type="button" class="rounded border border-rose-300 px-2 py-1 text-xs text-rose-700"
                                        :disabled="deletingPlaceId === place.id"
                                        @click="deletePlace(place.id, place.name)">
                                    {{ deletingPlaceId === place.id ? 'Deleting...' : 'Delete' }}
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div v-else class="rounded-xl border border-dashed border-slate-300 bg-white p-8 text-center text-slate-500">
            No organizations yet.
        </div>

        <div class="mt-5" v-if="places.links?.length">
            <Pagination :links="places.links" />
        </div>
    </AppLayout>
</template>
