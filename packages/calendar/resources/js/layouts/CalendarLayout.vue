<script setup lang="ts">
import AppLayout from '@coleus/support/layouts/AppLayout.vue';
</script>

<template>
    <AppLayout title="Calendar">
        <div class="flex h-full w-full flex-col overflow-hidden">
            <slot />
        </div>
    </AppLayout>
</template>
