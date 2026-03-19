<script setup>
import SyncStatusBadge from '@/Components/SyncStatusBadge.vue';

defineProps({
    rows: { type: Array, default: () => [] },
});

function formatDate(value) {
    if (!value) {
        return '-';
    }

    return new Date(value).toLocaleString();
}
</script>

<template>
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-600">
                <tr>
                    <th class="px-4 py-3 font-semibold">Status</th>
                    <th class="px-4 py-3 font-semibold">Started</th>
                    <th class="px-4 py-3 font-semibold">Finished</th>
                    <th class="px-4 py-3 font-semibold">Fetched</th>
                    <th class="px-4 py-3 font-semibold">Created</th>
                    <th class="px-4 py-3 font-semibold">Updated</th>
                    <th class="px-4 py-3 font-semibold">Error</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="row in rows" :key="row.id" class="border-t border-slate-100">
                    <td class="px-4 py-3"><SyncStatusBadge :status="row.status" /></td>
                    <td class="px-4 py-3">{{ formatDate(row.started_at) }}</td>
                    <td class="px-4 py-3">{{ formatDate(row.finished_at) }}</td>
                    <td class="px-4 py-3">{{ row.reviews_fetched }}</td>
                    <td class="px-4 py-3">{{ row.reviews_created }}</td>
                    <td class="px-4 py-3">{{ row.reviews_updated }}</td>
                    <td class="px-4 py-3 text-rose-600">{{ row.error_message || '-' }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
