<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import EmptyState from '@/Components/EmptyState.vue';
</script>

<template>
    <AppLayout>
        <EmptyState
            title="Settings"
            description="Integration source URLs are now managed on each organization page. This section is reserved for profile and global preferences."
        />
    </AppLayout>
</template>
