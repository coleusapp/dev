<script setup lang="ts">
import TableActions from '@/components/genres/TableActions.vue';
import { GenreCollection, GenreData } from '@/components/genres/genre';
import type { TableColumn } from '@nuxt/ui';
import { h } from 'vue';

defineProps<{
    collection?: GenreCollection;
}>();

const columns: TableColumn<GenreData>[] = [
    {
        accessorKey: 'name',
        header: 'Name',
    },
    {
        id: 'actions',
        cell: ({ row }) => h(TableActions, { genreId: row?.original?.id }),
    },
];
</script>

<template>
    <UiTable :data="collection?.data" :columns="columns" />
</template>
