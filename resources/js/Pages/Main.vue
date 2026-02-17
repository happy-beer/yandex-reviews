<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Dashboard" />
    <AuthenticatedLayout>

    </AuthenticatedLayout>
</template>

<script>
import { Link } from '@inertiajs/vue3';

export default {
    components: { Link },
};
</script>
