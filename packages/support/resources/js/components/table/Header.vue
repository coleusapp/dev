<template>
    <UiButton
        color="neutral"
        variant="ghost"
        :icon="isSorted
          ? isSorted === 'asc'
            ? 'i-lucide-arrow-up-narrow-wide'
            : 'i-lucide-arrow-down-wide-narrow'
          : 'i-lucide-arrow-up-down'"
        class="data-[state=open]:bg-elevated -mx-2.5"
        aria-label="Sort by ${isSorted === 'asc' ? 'descending' : 'ascending'}"
        @click="() => props.column.toggleSorting(props.column.getIsSorted() === 'asc')"
    >
        {{ label }}
    </UiButton>
</template>
<script setup lang="ts">
import type { Column } from '@tanstack/vue-table';

const props = defineProps<{
    column: Column<any>;
    label: string;
}>();
const isSorted = props.column.getIsSorted();
</script>
