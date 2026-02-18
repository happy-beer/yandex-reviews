<script setup>
import { defineProps, defineEmits, computed } from 'vue'

const props = defineProps({
    currentPage: { type: String, required: true },
    totalPages: { type: Number, required: true },
    maxVisibleButtons: {
        type: Number,
        required: false,
        default: 3
    },
})

props.currentPage = parseInt( props.currentPage);

const emit = defineEmits(['change'])

function goToPage(page) {
    if (page < 1 || page > props.totalPages || page === props.currentPage) return
    emit('change', page)
}
const pages = computed(() => {
    let { currentPage, totalPages } = props
    const result = []

    currentPage = parseInt(currentPage);

    if (totalPages === 0) return result

    result.push(1)

    if (currentPage - 1 > 2) {
        result.push('left-ellipsis')
    }

    for (let i = currentPage - 1; i <= currentPage + 1; i++) {
        if (i > 1 && i < totalPages) {
            result.push(i)
        }
    }

    if (currentPage + 1 < totalPages - 1) {
        result.push('right-ellipsis')
    }

    if (totalPages > 1) {
        result.push(totalPages)
    }

    return result
})

</script>

<template>
    <div class="flex items-center gap-1">
        <!-- Previous -->
        <button
            @click="goToPage(currentPage - 1)"
            :disabled="currentPage == 1"
            class="inline-flex items-center justify-center border select-none font-sans font-medium text-center transition-all duration-300 ease-in disabled:opacity-50 disabled:shadow-none disabled:cursor-not-allowed focus:shadow-none text-sm rounded-md py-2 px-4 bg-transparent border-transparent text-stone-800 hover:bg-stone-800/5 hover:border-stone-800/5 shadow-none hover:shadow-none"
        >
            <svg
                width="1.5em"
                height="1.5em"
                stroke-width="1.5"
                viewBox="0 0 24 24"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
                color="currentColor"
                class="mr-1.5 h-4 w-4 stroke-2"
            >
                <path d="M15 6L9 12L15 18" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Previous
        </button>

        <!-- Нумерация -->
        <template v-for="item in pages" :key="item + Math.random()">
            <span v-if="item === 'left-ellipsis' || item === 'right-ellipsis'" class="px-2">…</span>
            <button
                v-else
                @click="goToPage(item)"
                :class="[
          'inline-grid place-items-center border select-none font-sans font-medium text-center transition-all duration-300 ease-in text-sm min-w-[38px] min-h-[38px] rounded-md',
          item == currentPage
            ? 'bg-stone-800 border-stone-800 text-stone-50 shadow-sm hover:shadow-md hover:bg-stone-700 hover:border-stone-700'
            : 'bg-transparent border-transparent text-stone-800 hover:bg-stone-800/5 hover:border-stone-800/5 shadow-none hover:shadow-none'
        ]"
            >
                {{ item }}
            </button>
        </template>

        <!-- Next -->
        <button
            @click="goToPage(Number(currentPage) + 1)"
            :disabled="currentPage == totalPages"
            class="inline-flex items-center justify-center border select-none font-sans font-medium text-center transition-all duration-300 ease-in disabled:opacity-50 disabled:shadow-none disabled:cursor-not-allowed focus:shadow-none text-sm rounded-md py-2 px-4 bg-transparent border-transparent text-stone-800 hover:bg-stone-800/5 hover:border-stone-800/5 shadow-none hover:shadow-none"
        >
            Next
            <svg
                width="1.5em"
                height="1.5em"
                stroke-width="1.5"
                viewBox="0 0 24 24"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
                color="currentColor"
                class="ml-1.5 h-4 w-4 stroke-2"
            >
                <path d="M9 6L15 12L9 18" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>
    </div>
</template>
