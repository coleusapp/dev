<script setup lang="ts">
import EditCard from '@/components/playlists/EditCard.vue';
import { PlaylistResource, resourceKey, tracksKey } from '@/components/playlists/playlist';
import { OptionCollection } from '@/types';
import MusicLayout from '@/layouts/MusicLayout.vue';
import { provide } from 'vue';

const props = defineProps<{
    resource: PlaylistResource;
    tracks: OptionCollection;
}>();
provide(resourceKey, props.resource);
provide(tracksKey, props.tracks);
</script>

<template>
    <MusicLayout>
        <EditCard />
    </MusicLayout>
</template>
