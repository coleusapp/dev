<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps<{
    title: string;
}>();
</script>

<template>
    <div class="flex w-full items-center justify-between">
        <h1 class="text-xs font-bold uppercase">{{ title }}</h1>
        <div>
            <slot />
        </div>
    </div>
</template>
