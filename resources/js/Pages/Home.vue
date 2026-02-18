<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Rating from "@/Components/Rating.vue";
import ReviewsBlock from "@/Components/ReviewsBlock.vue";

const props = defineProps({
    name: String,
    rating: String,
    reviewCount: Number,
    pageSize: Number,
    settings: {
        type: Object,
        required: false,
    },
    reqData: {
        type: Object,
        required: false,
    },
})
</script>

<template>
    <AuthenticatedLayout>

        <div class="flex flex-row mt-6">
            <div class=" px-6 py-4 w-9/12">
                <div class="mb-2">
                    <a :href="settings.value"
                          target="_blank"
                          class="p-[4px] border w-auto inline-flex border-gray-200  transition-all ease-in  rounded-md
                                    disabled:opacity-50 disabled:shadow-none disabled:cursor-not-allowed
                                    focus:shadow-none duration-150
                                    hover:cursor-pointer
                                    "
                    >
                        <svg class="block mr-2" width="13" height="16" viewBox="0 0 13 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M6.0209 0C2.69556 0 0 2.69556 0 6.0209C0 7.68297 0.673438 9.1879 1.76262 10.2774C2.8521 11.3675 5.41881 12.9449 5.56934 14.6007C5.5919 14.849 5.77164 15.0523 6.0209 15.0523C6.27017 15.0523 6.4499 14.849 6.47247 14.6007C6.62299 12.9449 9.1897 11.3675 10.2792 10.2774C11.3684 9.1879 12.0418 7.68297 12.0418 6.0209C12.0418 2.69556 9.34625 0 6.0209 0Z"
                                fill="#FF4433"/>
                            <path
                                d="M6.10732 8.21463C7.27116 8.21463 8.21461 7.27115 8.21461 6.10732C8.21461 4.94348 7.27116 4 6.10732 4C4.94349 4 4 4.94348 4 6.10732C4 7.27115 4.94349 8.21463 6.10732 8.21463Z"
                                fill="white"/>
                        </svg>
                        <span class="block">Яндекс Карты</span>
                    </a>
                </div>
                <ReviewsBlock :organizationName="name" :reqData="reqData" :pageSize="pageSize"/>
            </div>
            <div class="rounded-2xl shadow-lg shadow-gray-300 mx-6 p-6 h-50 w-3/12">
                <div class="flex items-center border-b-2 pb-6 border-b-gray-200">
                    <span class="text-5xl font-medium mr-2">{{ rating }}</span>
                    <Rating :rating="rating"  :size="8" :space="3"/>
                </div>

                <div class="font-medium text-xl mt-4">
                    Всего отзывов: {{ reviewCount }}
                </div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>

<script>
import {Link} from '@inertiajs/vue3';

export default {
    components: {Link},
};
</script>
