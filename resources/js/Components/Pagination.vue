<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    links: {
        type: [Array, Object],
        default: () => ([]),
    },
    meta: {
        type: Object,
        default: () => ({}),
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

const pageLinks = computed(() => {
    if (Array.isArray(props.links)) {
        return props.links;
    }

    if (Array.isArray(props.meta?.links)) {
        return props.meta.links;
    }

    return [];
});

const navLinks = computed(() => {
    if (props.links && !Array.isArray(props.links) && typeof props.links === 'object') {
        return props.links;
    }

    return {};
});

const isLinkMode = computed(() =>
    pageLinks.value.length > 0
    || Boolean(props.meta?.first_page_url || navLinks.value.first)
);

const currentPage = computed(() => Number(props.meta?.current_page || props.currentPage || 1));
const lastPage = computed(() => Number(props.meta?.last_page || props.totalPages || 1));
const canGoFirst = computed(() => Boolean(firstPageUrl.value) && currentPage.value > 1);
const canGoPrev = computed(() => Boolean(prevPageUrl.value) && currentPage.value > 1);
const canGoNext = computed(() => Boolean(nextPageUrl.value) && currentPage.value < lastPage.value);
const canGoLast = computed(() => Boolean(lastPageUrl.value) && currentPage.value < lastPage.value);

const hasMultiplePages = computed(() => {
    const metaLastPage = lastPage.value;
    if (metaLastPage > 1) {
        return true;
    }

    const numericPageLinks = pageLinks.value.filter((link) => {
        const label = String(link?.label ?? '').trim();
        return /^\d+$/.test(label);
    });

    if (numericPageLinks.length > 1) {
        return true;
    }

    return Boolean(
        pageLinks.value.some((link) => {
            const label = String(link?.label ?? '');
            return (label.includes('Previous') || label.includes('Next')) && link?.url;
        })
    );
});

const numericLinks = computed(() => pageLinks.value.filter((link) => /^\d+$/.test(String(link?.label ?? '').trim())));
const pageLinkByNumber = computed(() => {
    const map = new Map();

    for (const link of numericLinks.value) {
        map.set(Number(String(link.label).trim()), link);
    }

    return map;
});

const visiblePaginationItems = computed(() => {
    const current = currentPage.value;
    const last = lastPage.value;

    if (last <= 1) {
        return [];
    }

    const pages = new Set([1, last]);
    const from = Math.max(2, current - 2);
    const to = Math.min(last - 1, current + 2);

    for (let page = from; page <= to; page += 1) {
        pages.add(page);
    }

    const sortedPages = Array.from(pages).sort((a, b) => a - b);
    const items = [];
    let previousPage = null;

    for (const page of sortedPages) {
        if (previousPage !== null && page - previousPage > 1) {
            items.push({
                type: 'ellipsis',
                key: `ellipsis-${previousPage}-${page}`,
                label: '...',
            });
        }

        const link = pageLinkByNumber.value.get(page) || {
            label: String(page),
            url: null,
            active: page === current,
        };

        items.push({
            type: 'page',
            key: `page-${page}`,
            page,
            link,
        });

        previousPage = page;
    }

    return items;
});

const firstPageUrl = computed(() =>
    props.meta?.first_page_url
    || navLinks.value.first
    || numericLinks.value[0]?.url
    || null
);
const prevPageUrl = computed(() => {
    if (props.meta?.prev_page_url) {
        return props.meta.prev_page_url;
    }
    if (navLinks.value.prev) {
        return navLinks.value.prev;
    }
    return pageLinks.value.find((link) => String(link?.label ?? '').includes('Previous'))?.url || null;
});
const nextPageUrl = computed(() => {
    if (props.meta?.next_page_url) {
        return props.meta.next_page_url;
    }
    if (navLinks.value.next) {
        return navLinks.value.next;
    }
    return pageLinks.value.find((link) => String(link?.label ?? '').includes('Next'))?.url || null;
});
const lastPageUrl = computed(() =>
    props.meta?.last_page_url
    || navLinks.value.last
    || numericLinks.value[numericLinks.value.length - 1]?.url
    || null
);

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
    <div v-if="isLinkMode && hasMultiplePages" class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-xs font-medium text-slate-500">
            Page {{ currentPage }} of {{ lastPage }}
        </p>

        <nav class="inline-flex items-center gap-1 rounded-xl border border-slate-200 bg-white p-1 shadow-sm">
            <span
                v-if="!canGoFirst"
                class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-slate-300"
            >
                First
            </span>
            <Link
                v-else
                :href="firstPageUrl"
                class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-100 hover:text-slate-900"
            >
                First
            </Link>

            <span
                v-if="!canGoPrev"
                class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-slate-300"
            >
                Prev
            </span>
            <Link
                v-else
                :href="prevPageUrl"
                class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-100 hover:text-slate-900"
            >
                Prev
            </Link>

            <div class="hidden items-center gap-1 px-1 sm:flex">
                <template v-for="item in visiblePaginationItems" :key="item.key">
                    <span
                        v-if="item.type === 'ellipsis'"
                        class="rounded-lg px-2 py-1.5 text-xs font-semibold text-slate-400"
                    >
                        ...
                    </span>
                    <span
                        v-else-if="item.link.url === null"
                        class="rounded-lg px-2 py-1.5 text-xs font-semibold text-slate-400"
                        v-html="item.link.label"
                    />
                    <Link
                        v-else
                        :href="item.link.url"
                        class="min-w-8 rounded-lg px-2 py-1.5 text-center text-xs font-semibold transition"
                        :class="item.link.active ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
                        v-html="item.link.label"
                    />
                </template>
            </div>

            <span
                v-if="!canGoNext"
                class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-slate-300"
            >
                Next
            </span>
            <Link
                v-else
                :href="nextPageUrl"
                class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-100 hover:text-slate-900"
            >
                Next
            </Link>

            <span
                v-if="!canGoLast"
                class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-slate-300"
            >
                Last
            </span>
            <Link
                v-else
                :href="lastPageUrl"
                class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-100 hover:text-slate-900"
            >
                Last
            </Link>
        </nav>
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
