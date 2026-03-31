<script setup lang="ts">
import TableCard from '@/components/artists/TableCard.vue';
import { ArtistCollection } from '@/components/artists/artist';
import MusicLayout from '@/layouts/MusicLayout.vue';

defineProps<{
    collection?: ArtistCollection;
}>();
</script>

<template>
    <MusicLayout>
        <TableCard :collection="collection" />
    </MusicLayout>
</template>
