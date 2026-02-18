<template>
    <div
         :class="'flex '+ [spaceClass]"
    >
        <template v-for="i in 5" :key="i">
            <svg
                v-if="i <= Math.floor(rating)"
                :class="[sizeClass]"
                fill="currentColor"
                width="23" height="22" viewBox="0 0 23 22" xmlns="http://www.w3.org/2000/svg">
                <path
                    d="M14.5342 6.78711L14.6768 7.02441L14.9473 7.08691L21.2198 8.53906L16.9991 13.4023L16.8174 13.6113L16.8409 13.8877L17.3975 20.3027L11.4698 17.791L11.2139 17.6826L10.9581 17.791L5.02936 20.3027L5.58698 13.8877L5.61041 13.6113L5.42877 13.4023L1.20709 8.53906L7.48053 7.08691L7.75104 7.02441L7.89362 6.78711L11.2139 1.27051L14.5342 6.78711Z"
                    fill="#FBBC04" stroke="#FBBC04" stroke-width="1.31012"/>
            </svg>
            <svg
                v-else
                :class="[sizeClass]"
                fill="none"
                width="23" height="22" viewBox="0 0 23 22" xmlns="http://www.w3.org/2000/svg">
                <path
                    d="M14.8809 6.57812L14.9356 6.66895L15.0391 6.69238L21.9669 8.29688L17.3047 13.668L17.2354 13.748L17.2452 13.8535L17.8594 20.9375L11.3116 18.1641L11.2139 18.123L11.1163 18.1641L4.56744 20.9375L5.18365 13.8535L5.19244 13.748L5.12311 13.668L0.460022 8.29688L7.38971 6.69238L7.49225 6.66895L7.54694 6.57812L11.2139 0.483398L14.8809 6.57812Z"
                    stroke="#6C757D" stroke-width="0.5"/>
            </svg>
        </template>
    </div>
</template>

<script setup>
import { computed } from 'vue';
const props = defineProps({
    rating: {
        required: true
    },
    size: {
        type: Number,
        default: 4
    },
    space: {
        type: Number,
        default: 1.5
    }
});
const sizeClass = `w-${props.size} h-${props.size}`
const spaceClass = `space-x-${props.space}`
</script>
