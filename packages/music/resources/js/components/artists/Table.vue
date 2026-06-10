<script setup lang="ts">
import TableActions from '@/components/artists/TableActions.vue';
import { ArtistCollection, ArtistData } from '@/components/artists/artist';
import type { TableColumn } from '@nuxt/ui';
import { h } from 'vue';

defineProps<{
    collection?: ArtistCollection;
}>();

const columns: TableColumn<ArtistData>[] = [
    {
        accessorKey: 'name',
        header: 'Name',
    },
    {
        accessorKey: 'bio',
        header: 'Bio',
    },
    {
        id: 'actions',
        cell: ({ row }) => h(TableActions, { artistId: row?.original?.id }),
    },
];
</script>

<template>
    <UiTable :data="collection?.data" :columns="columns" />
</template>
