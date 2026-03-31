<script setup lang="ts">
import TableCard from '@/components/playlists/TableCard.vue';
import { PlaylistCollection } from '@/components/playlists/playlist';
import MusicLayout from '@/layouts/MusicLayout.vue';

defineProps<{
    collection?: PlaylistCollection;
}>();
</script>

<template>
    <MusicLayout>
        <TableCard :collection="collection" />
    </MusicLayout>
</template>
