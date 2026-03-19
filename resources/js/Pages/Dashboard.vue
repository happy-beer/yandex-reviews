<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import StatCard from '@/Components/StatCard.vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    stats: {
        type: Object,
        required: true,
    },
});

function formatDate(value) {
    if (!value) {
        return 'No sync yet';
    }

    return new Date(value).toLocaleString();
}
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout>
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-slate-900">Dashboard</h1>
            <p class="mt-1 text-sm text-slate-500">Snapshot of organizations and reviews.</p>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <StatCard title="Organizations" :value="stats.places_count" />
            <StatCard title="Reviews" :value="stats.reviews_count" />
            <StatCard title="Average rating" :value="stats.average_rating ?? 'n/a'" />
            <StatCard title="Last sync" :value="formatDate(stats.last_synced_at)" />
        </div>
    </AppLayout>
</template>
