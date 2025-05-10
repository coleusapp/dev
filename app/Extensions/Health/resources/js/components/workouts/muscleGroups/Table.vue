<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui';
import { h } from 'vue';
import TableActions from '@health/components/workouts/muscleGroups/parts/TableActions.vue';
import { Collection, Data } from '@health/components/workouts/muscleGroups/type';

defineProps<{
    collection?: Collection;
}>();

const columns: TableColumn<Data>[] = [
    {
        accessorKey: 'name',
        header: 'Name',
    },
    {
        id: 'actions',
        cell: ({ row }) => h(TableActions, { muscleGroupId: row?.original?.id }),
    },
];
</script>

<template>
    <UiTable :data="collection?.data" :columns="columns" />
</template>
