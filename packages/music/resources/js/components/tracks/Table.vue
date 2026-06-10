<script setup lang="ts">
import TableActions from '@/components/tracks/TableActions.vue';
import { TrackCollection, TrackData } from '@/components/tracks/track';
import type { TableColumn } from '@nuxt/ui';
import { h } from 'vue';

defineProps<{
    collection?: TrackCollection;
}>();

const columns: TableColumn<TrackData>[] = [
    {
        accessorKey: 'title',
        header: 'Title',
    },
    {
        accessorKey: 'artist',
        header: 'Artist',
    },
    {
        accessorKey: 'album',
        header: 'Album',
    },
    {
        accessorKey: 'genre',
        header: 'Genre',
    },
    {
        accessorKey: 'duration',
        header: 'Duration (s)',
    },
    {
        accessorKey: 'track_number',
        header: '#',
    },
    {
        id: 'actions',
        cell: ({ row }) => h(TableActions, { trackId: row?.original?.id }),
    },
];
</script>

<template>
    <UiTable :data="collection?.data" :columns="columns" />
</template>
