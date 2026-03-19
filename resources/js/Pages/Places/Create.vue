<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    source_url: '',
    is_active: true,
});

function submit() {
    form.post('/places');
}
</script>

<template>
    <AppLayout>
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-3xl font-bold text-slate-900">Create organization</h1>
            <Link href="/places" class="text-sm font-semibold text-slate-600 hover:text-slate-900">Back to list</Link>
        </div>

        <form class="max-w-2xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm" @submit.prevent="submit">
            <div class="space-y-5">
                <div>
                    <InputLabel for="name" value="Name" />
                    <TextInput id="name" v-model="form.name" class="mt-1 block w-full" required autofocus />
                    <InputError class="mt-2" :message="form.errors.name" />
                </div>

                <div>
                    <InputLabel for="source_url" value="Yandex source URL" />
                    <TextInput id="source_url" v-model="form.source_url" class="mt-1 block w-full" required />
                    <InputError class="mt-2" :message="form.errors.source_url" />
                </div>

                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                    <input v-model="form.is_active" type="checkbox" class="rounded border-slate-300" />
                    Active organization
                </label>

                <div class="pt-2">
                    <PrimaryButton :disabled="form.processing">{{ form.processing ? 'Saving...' : 'Create place' }}</PrimaryButton>
                </div>
            </div>
        </form>
    </AppLayout>
</template>
