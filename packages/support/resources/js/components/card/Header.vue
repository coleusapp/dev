<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps<{
    title: string;
}>();
</script>

<template>
    <div class="flex w-full items-center justify-between">
        <div class="flex items-center gap-2">
            <h1 class="text-xs font-bold uppercase">{{ title }}</h1>
            <slot name="title-suffix" />
        </div>
        <div>
            <slot />
        </div>
    </div>
</template>
