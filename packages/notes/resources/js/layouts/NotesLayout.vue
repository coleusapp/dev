<script setup lang="ts">
import AppLayout from '@coleus/support/layouts/AppLayout.vue';
</script>

<template>
    <AppLayout title="Notes">
        <template v-if="$slots.panels" #panels>
            <slot name="panels" />
        </template>

        <div class="flex h-full w-full flex-col overflow-hidden">
            <slot />
        </div>
    </AppLayout>
</template>
