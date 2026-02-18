<script setup>
import Rating from './Rating.vue'
import {defineProps, computed} from 'vue'

const props = defineProps({
    organizationName: String,
    review: {
        type: Object,
        required: true
    },
})

const formattedDate = computed(() => {
    const d = new Date(props.review.updatedTime);
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();
    const hours = String(d.getHours()).padStart(2, '0');
    const minutes = String(d.getMinutes()).padStart(2, '0');
    return `${day}.${month}.${year} ${hours}:${minutes}`;
})
</script>
<template>
    <div class="py-4 pl-4 bg-white rounded-2xl shadow-md space-y-2 border border-gray-100">
        <div class="pt-1 pb-2 pr-4 bg-slate-50 rounded-lg">
            <div class="flex items-center justify-between mb-2">
                <div class="flex items-center space-x-2 text-gray-500 text-base">
                    <span class="font-semibold text-gray-700">{{ formattedDate }}</span>
                    <span class="font-semibold text-gray-700 ml-2">{{ organizationName }}</span>
                    <svg width="13" height="16" viewBox="0 0 13 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M6.0209 0C2.69556 0 0 2.69556 0 6.0209C0 7.68297 0.673438 9.1879 1.76262 10.2774C2.8521 11.3675 5.41881 12.9449 5.56934 14.6007C5.5919 14.849 5.77164 15.0523 6.0209 15.0523C6.27017 15.0523 6.4499 14.849 6.47247 14.6007C6.62299 12.9449 9.1897 11.3675 10.2792 10.2774C11.3684 9.1879 12.0418 7.68297 12.0418 6.0209C12.0418 2.69556 9.34625 0 6.0209 0Z"
                            fill="#FF4433"/>
                        <path
                            d="M6.10732 8.21463C7.27116 8.21463 8.21461 7.27115 8.21461 6.10732C8.21461 4.94348 7.27116 4 6.10732 4C4.94349 4 4 4.94348 4 6.10732C4 7.27115 4.94349 8.21463 6.10732 8.21463Z"
                            fill="white"/>
                    </svg>
                </div>
                <div class="">
                    <Rating :rating="review.rating"/>
                </div>
            </div>

            <div class="text-gray-700">
                <span class="font-semibold text-base">{{ review.author.name }}</span>
                <!-- there is no field like phone so for ex professionLevel-->
                <span class="font-semibold text-xs ml-2">{{ review.author.professionLevel }}</span>
            </div>

            <div class="text-gray-700 whitespace-pre-line text-xs">
                {{ review.text }}
            </div>
        </div>
    </div>
</template>
