<script setup>
import {ref, onMounted, defineProps} from 'vue'
import ReviewCard from './ReviewCard.vue'
import Pagination from './Pagination.vue'

const props = defineProps({
    organizationName: String,
    cookie: String,
    pageSize: Number,
    reqData: {
        type: Object,
        required: false,
    },
})

const reviews = ref([])
const params = ref([])
const loading = ref(true)
const error = ref(null)
const csrfToken = ref(null)

const currentPage = ref(1)
const totalPages = ref(1)


async function loadPage(page = 1) {
    loading.value = true
    error.value = null
    csrfToken.value = props.reqData.csrfToken

    try {
        const query = new URLSearchParams({
            cookie: props.cookie,
            businessId: props.reqData.businessId,
            csrfToken: csrfToken.value,
            sessionId: props.reqData.sessionId,
            reqId: props.reqData.reqId,
            page,
            pageSize: props.pageSize,
        }).toString()

        const res = await fetch(`/api/reviews?${query}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        })

        if (!res.ok) throw new Error('Ошибка загрузки отзывов')

        const json = await res.json()

        props.cookie = json.cookie
        reviews.value = json.reviews
        params.value = json.params
        csrfToken.value = json.params.csrfToken
            currentPage.value = params.value.page
        totalPages.value = params.value.totalPages
    } catch (e) {
        error.value = e.message
    } finally {
        loading.value = false
    }
}

// загрузка первой страницы при монтировании
onMounted(() => loadPage(1))

function handlePageChange(page) {
    loadPage(page)
}
</script>

<template>
    <div>
        <div v-if="loading">Загрузка…</div>
        <div v-else-if="error">{{ error }}</div>
        <div v-else-if="reviews && reviews.length > 0" class="flex-1 flex flex-col ">
            <div class="space-y-4 mb-3 max-h-[60vh] overflow-y-auto">
            <ReviewCard
                v-for="review in reviews"
                :key="review.id"
                :review="review"
                :organizationName="organizationName"
            />
            </div>
            <Pagination
                :currentPage="currentPage"
                :totalPages="totalPages"
                @change="handlePageChange"
            />
        </div>
        <div v-else>Отзывы не загружены или отсутствуют</div>
    </div>
</template>
