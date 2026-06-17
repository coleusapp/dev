<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps<{
    href: string;
    disabled?: boolean;
}>();
</script>
<template>
    <UiTooltip :text="disabled ? 'Cannot delete, currently in use' : 'Delete'">
        <Link v-if="!disabled" :href="href" method="delete" @success="() => $toast.add({ title: 'Deleted successfully!' })">
            <UiButton icon="i-lucide-trash" variant="ghost" color="error" />
        </Link>
        <UiButton v-else icon="i-lucide-trash" variant="ghost" color="error" disabled />
    </UiTooltip>
</template>
