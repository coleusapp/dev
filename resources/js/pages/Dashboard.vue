<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
</script>

<template>
    <AppLayout>
        <div class="mx-auto w-full max-w-7xl">
            <slot />
        </div>
    </AppLayout>
</template>
