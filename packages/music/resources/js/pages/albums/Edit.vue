<script setup lang="ts">
import EditCard from '@/components/albums/EditCard.vue';
import { AlbumResource, artistsKey, resourceKey } from '@/components/albums/album';
import { OptionCollection } from '@/types';
import MusicLayout from '@/layouts/MusicLayout.vue';
import { provide } from 'vue';

const props = defineProps<{
    resource: AlbumResource;
    artists: OptionCollection;
}>();
provide(resourceKey, props.resource);
provide(artistsKey, props.artists);
</script>

<template>
    <MusicLayout>
        <EditCard />
    </MusicLayout>
</template>
