<script setup lang="ts">
import CreateCard from '@/components/albums/CreateCard.vue';
import { artistsKey } from '@/components/albums/album';
import { OptionCollection } from '@/types';
import MusicLayout from '@/layouts/MusicLayout.vue';
import { provide } from 'vue';

const props = defineProps<{
    artists: OptionCollection;
}>();
provide(artistsKey, props.artists);
</script>

<template>
    <MusicLayout>
        <CreateCard />
    </MusicLayout>
</template>
