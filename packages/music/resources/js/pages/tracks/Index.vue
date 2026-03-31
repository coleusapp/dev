<script setup lang="ts">
import TableCard from '@/components/tracks/TableCard.vue';
import { TrackCollection } from '@/components/tracks/track';
import MusicLayout from '@/layouts/MusicLayout.vue';

defineProps<{
    collection?: TrackCollection;
}>();
</script>

<template>
    <MusicLayout>
        <TableCard :collection="collection" />
    </MusicLayout>
</template>
