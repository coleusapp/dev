<script setup lang="ts">
import EditCard from '@/components/artists/EditCard.vue';
import { ArtistResource, resourceKey } from '@/components/artists/artist';
import MusicLayout from '@/layouts/MusicLayout.vue';
import { provide } from 'vue';

const props = defineProps<{
    resource: ArtistResource;
}>();
provide(resourceKey, props.resource);
</script>

<template>
    <MusicLayout>
        <EditCard />
    </MusicLayout>
</template>
