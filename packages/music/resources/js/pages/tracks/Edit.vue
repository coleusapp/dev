<script setup lang="ts">
import EditCard from '@/components/tracks/EditCard.vue';
import { TrackResource, albumsKey, artistsKey, genresKey, resourceKey } from '@/components/tracks/track';
import { OptionCollection } from '@/types';
import MusicLayout from '@/layouts/MusicLayout.vue';
import { provide } from 'vue';

const props = defineProps<{
    resource: TrackResource;
    artists: OptionCollection;
    albums: OptionCollection;
    genres: OptionCollection;
}>();
provide(resourceKey, props.resource);
provide(artistsKey, props.artists);
provide(albumsKey, props.albums);
provide(genresKey, props.genres);
</script>

<template>
    <MusicLayout>
        <EditCard />
    </MusicLayout>
</template>
