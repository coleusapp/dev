<script setup lang="ts">
import CreateCard from '@/components/playlists/CreateCard.vue';
import { tracksKey } from '@/components/playlists/playlist';
import { OptionCollection } from '@/types';
import MusicLayout from '@/layouts/MusicLayout.vue';
import { provide } from 'vue';

const props = defineProps<{
    tracks: OptionCollection;
}>();
provide(tracksKey, props.tracks);
</script>

<template>
    <MusicLayout>
        <CreateCard />
    </MusicLayout>
</template>
