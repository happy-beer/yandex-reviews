<template>
    <AuthenticatedLayout>
        <h1 class="text-2xl font-bold mb-6">Подключить Яндекс</h1>

        <p v-if="flash?.success" class="text-green-600 mt-2">Настройки сохранены!</p>

        <form @submit.prevent="submit" class="space-y-6">
            <div v-for="(setting, index) in form.settings" :key="setting.key">
                <label class="block font-medium text-gray-500 text-xl mb-3" v-html="getLabelByKey(setting.key)">
                </label>
                <input
                    v-model="form.settings[index].value"
                    type="text"
                    class="inline-block w-lg max-w-xl px-3 py-2 border border-gray-400 text-gray-400 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                />
                <span v-if="form.errors['settings.0.value']" class="text-red-600">{{ form.errors['settings.0.value'] }}</span>
            </div>

            <button
                type="submit"
                class="px-5 py-1 bg-blue-400 font-bold text-base text-white rounded hover:bg-blue-600"
            >
                Сохранить
            </button>

        </form>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, reactive, watch, defineProps } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    settings: {
        type: Array,
        required: true,
    },
    errors: {
        type: Object,
        required: false,
    },
    flash: {
        type: Object,
        required: false,
    },
});

const form = useForm({
    settings: props.settings.map(s => ({ key: s.key, value: s.value }))
});


const errors = ref(props.errors || {});
const success = ref(false);

watch(form, () => success.value = false, { deep: true });

function submit() {
    form.post('/settings', form, {
        onSuccess: () => {
            success.value = true;
        },
    });
}

function getLabelByKey(key) {
    const map = {
        yandex_url: 'Укажите ссылку на Яндекс, пример - <span class="font-normal text-gray-400 underline text-base">https://yandex.ru/maps/org/samoye_populyarnoye_kafe/1010501395/reviews/</span>',
    };
    return map[key] || key;
}
</script>
