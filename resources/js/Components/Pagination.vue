<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    links: {
        type: Array,
        default: () => [],
    },
    currentPage: {
        type: [String, Number],
        default: null,
    },
    totalPages: {
        type: Number,
        default: null,
    },
});

const emit = defineEmits(['change']);

const isLinkMode = computed(() => props.links.length > 0);

const current = computed(() => {
    if (props.currentPage === null || props.currentPage === undefined) {
        return 1;
    }
    return Number(props.currentPage);
});

function goToPage(page) {
    if (!props.totalPages || page < 1 || page > props.totalPages || page === current.value) {
        return;
    }
    emit('change', page);
}
</script>

<template>
    <div v-if="isLinkMode" class="flex flex-wrap gap-1">
        <template v-for="(link, index) in links" :key="`${index}-${link.label}`">
            <span v-if="link.url === null" class="rounded-md border border-slate-200 px-3 py-1.5 text-sm text-slate-400"
                  v-html="link.label" />
            <Link
                v-else
                :href="link.url"
                class="rounded-md border px-3 py-1.5 text-sm transition"
                :class="link.active ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-300 text-slate-700 hover:bg-slate-100'"
                v-html="link.label"
            />
        </template>
    </div>

    <div v-else class="flex items-center gap-2">
        <button type="button" class="rounded-md border border-slate-300 px-3 py-1.5 text-sm"
                :disabled="current <= 1" @click="goToPage(current - 1)">
            Previous
        </button>
        <span class="text-sm text-slate-600">{{ current }} / {{ totalPages }}</span>
        <button type="button" class="rounded-md border border-slate-300 px-3 py-1.5 text-sm"
                :disabled="!totalPages || current >= totalPages" @click="goToPage(current + 1)">
            Next
        </button>
    </div>
</template>
