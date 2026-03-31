<script setup lang="ts">
import TableCard from '@/components/genres/TableCard.vue';
import { GenreCollection } from '@/components/genres/genre';
import MusicLayout from '@/layouts/MusicLayout.vue';

defineProps<{
    collection?: GenreCollection;
}>();
</script>

<template>
    <MusicLayout>
        <TableCard :collection="collection" />
    </MusicLayout>
</template>
