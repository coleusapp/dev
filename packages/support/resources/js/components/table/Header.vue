<template>
    <UiDropdownMenu :content="{ align: 'start' }" aria-label="Actions dropdown" :items="items">
        <UiButton
            color="neutral"
            variant="ghost"
            :icon="isSorted ? (isSorted === 'asc' ? 'i-lucide-arrow-up-narrow-wide' : 'i-lucide-arrow-down-wide-narrow') : 'i-lucide-arrow-up-down'"
            class="data-[state=open]:bg-elevated -mx-2.5"
            aria-label="Sort by ${isSorted === 'asc' ? 'descending' : 'ascending'}"
        >
            {{ label }}
        </UiButton>
    </UiDropdownMenu>
</template>
<script setup lang="ts">
import type { Column } from '@tanstack/vue-table';
import { ref } from 'vue';

const props = defineProps<{
    column: Column;
    label: string;
}>();
const isSorted = props.column.getIsSorted();
const items = ref([
    {
        label: 'Asc',
        type: 'checkbox',
        icon: 'i-lucide-arrow-up-narrow-wide',
        checked: isSorted === 'asc',
        onSelect: () => {
            if (isSorted === 'asc') {
                props.column.clearSorting();
            } else {
                props.column.toggleSorting(false);
            }
        },
    },
    {
        label: 'Desc',
        icon: 'i-lucide-arrow-down-wide-narrow',
        type: 'checkbox',
        checked: isSorted === 'desc',
        onSelect: () => {
            if (isSorted === 'desc') {
                props.column.clearSorting();
            } else {
                props.column.toggleSorting(true);
            }
        },
    },
]);
</script>
