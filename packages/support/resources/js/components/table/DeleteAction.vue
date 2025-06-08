<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps<{
    href: string;
}>();
</script>
<template>
    <UiTooltip text="Delete">
        <Link :href="href" method="delete" @success="() => $toast.add({ title: 'Deleted successfully!' })">
            <UiButton icon="i-lucide-trash" variant="ghost" color="error"></UiButton>
        </Link>
    </UiTooltip>
</template>
