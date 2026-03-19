<script setup>
import { reactive, watch } from 'vue';

const props = defineProps({
    filters: { type: Object, required: true },
    places: { type: Array, default: () => [] },
    withPlace: { type: Boolean, default: true },
});

const emit = defineEmits(['apply', 'reset']);

const form = reactive({
    place_id: props.filters.place_id ?? '',
    rating: props.filters.rating ?? '',
    search: props.filters.search ?? '',
    date_from: props.filters.date_from ?? '',
    date_to: props.filters.date_to ?? '',
    sort: props.filters.sort ?? 'newest',
});

watch(() => props.filters, (value) => {
    form.place_id = value.place_id ?? '';
    form.rating = value.rating ?? '';
    form.search = value.search ?? '';
    form.date_from = value.date_from ?? '';
    form.date_to = value.date_to ?? '';
    form.sort = value.sort ?? 'newest';
}, { deep: true });

function apply() {
    emit('apply', { ...form });
}

function reset() {
    form.place_id = '';
    form.rating = '';
    form.search = '';
    form.date_from = '';
    form.date_to = '';
    form.sort = 'newest';
    emit('reset', { ...form });
}
</script>

<template>
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
            <select v-if="withPlace" v-model="form.place_id" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="">All organizations</option>
                <option v-for="place in places" :key="place.id" :value="place.id">{{ place.name }}</option>
            </select>

            <select v-model="form.rating" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="">Any rating</option>
                <option v-for="num in [5,4,3,2,1]" :key="num" :value="num">{{ num }} stars</option>
            </select>

            <select v-model="form.sort" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="newest">Newest</option>
                <option value="oldest">Oldest</option>
                <option value="rating_desc">Rating desc</option>
                <option value="rating_asc">Rating asc</option>
            </select>

            <input v-model="form.search" type="text" placeholder="Search text/author"
                   class="rounded-lg border border-slate-300 px-3 py-2 text-sm" />
            <input v-model="form.date_from" type="date" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" />
            <input v-model="form.date_to" type="date" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" />
        </div>

        <div class="mt-4 flex gap-2">
            <button type="button" @click="apply" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Apply</button>
            <button type="button" @click="reset" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Reset</button>
        </div>
    </div>
</template>
