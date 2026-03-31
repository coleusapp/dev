<script setup lang="ts">
import TableCard from '@/components/albums/TableCard.vue';
import { AlbumCollection } from '@/components/albums/album';
import MusicLayout from '@/layouts/MusicLayout.vue';

defineProps<{
    collection?: AlbumCollection;
}>();
</script>

<template>
    <MusicLayout>
        <TableCard :collection="collection" />
    </MusicLayout>
</template>
