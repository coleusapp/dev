<script setup lang="ts">
import CreateCard from '@/components/tracks/CreateCard.vue';
import { albumsKey, artistsKey, genresKey } from '@/components/tracks/track';
import { OptionCollection } from '@/types';
import MusicLayout from '@/layouts/MusicLayout.vue';
import { provide } from 'vue';

const props = defineProps<{
    artists: OptionCollection;
    albums: OptionCollection;
    genres: OptionCollection;
}>();
provide(artistsKey, props.artists);
provide(albumsKey, props.albums);
provide(genresKey, props.genres);
</script>

<template>
    <MusicLayout>
        <CreateCard />
    </MusicLayout>
</template>
