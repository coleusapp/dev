<script setup lang="ts">
import TableActions from '@/components/albums/TableActions.vue';
import { AlbumCollection, AlbumData } from '@/components/albums/album';
import type { TableColumn } from '@nuxt/ui';
import { h } from 'vue';

defineProps<{
    collection?: AlbumCollection;
}>();

const columns: TableColumn<AlbumData>[] = [
    {
        accessorKey: 'title',
        header: 'Title',
    },
    {
        accessorKey: 'artist',
        header: 'Artist',
    },
    {
        accessorKey: 'release_date',
        header: 'Release Date',
    },
    {
        id: 'actions',
        cell: ({ row }) => h(TableActions, { albumId: row?.original?.id }),
    },
];
</script>

<template>
    <UiTable :data="collection?.data" :columns="columns" />
</template>
