<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps<{
    href: string;
}>();
</script>
<template>
    <UiTooltip text="Edit">
        <Link :href="href">
            <UiButton icon="i-lucide-pencil" variant="ghost" color="neutral"></UiButton>
        </Link>
    </UiTooltip>
</template>
