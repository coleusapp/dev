<script setup lang="ts">
import TableActions from '@/components/playlists/TableActions.vue';
import { PlaylistCollection, PlaylistData } from '@/components/playlists/playlist';
import type { TableColumn } from '@nuxt/ui';
import { h } from 'vue';

defineProps<{
    collection?: PlaylistCollection;
}>();

const columns: TableColumn<PlaylistData>[] = [
    {
        accessorKey: 'name',
        header: 'Name',
    },
    {
        accessorKey: 'description',
        header: 'Description',
    },
    {
        id: 'actions',
        cell: ({ row }) => h(TableActions, { playlistId: row?.original?.id }),
    },
];
</script>

<template>
    <UiTable :data="collection?.data" :columns="columns" />
</template>
