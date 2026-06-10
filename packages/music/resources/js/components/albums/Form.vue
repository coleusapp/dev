<script setup lang="ts">
import { AlbumRequest, artistsKey } from '@/components/albums/album';
import { OptionCollection } from '@/types';
import { inject } from 'vue';

defineProps<{
    value: AlbumRequest;
}>();

const artists = inject(artistsKey) as OptionCollection;
</script>

<template>
    <FormKit
        type="text"
        name="title"
        label="Title"
        validation="required"
    />
    <FormKit
        type="select"
        name="artist_id"
        label="Artist"
        :options="artists?.data"
        validation="required"
    />
    <FormKit
        type="date"
        name="release_date"
        label="Release Date"
    />
</template>
