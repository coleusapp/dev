<script setup lang="ts">
import { TrackRequest, albumsKey, artistsKey, genresKey } from '@/components/tracks/track';
import { OptionCollection } from '@/types';
import { inject } from 'vue';

defineProps<{
    value: TrackRequest;
    currentPath?: string | null;
}>();

const file = defineModel<File | null>('file', { default: null });

const artists = inject(artistsKey) as OptionCollection;
const albums = inject(albumsKey) as OptionCollection;
const genres = inject(genresKey) as OptionCollection;

const handleFileChange = (event: Event) => {
    const input = event.target as HTMLInputElement;
    file.value = input.files?.[0] ?? null;
};
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
        type="select"
        name="album_id"
        label="Album"
        :options="albums?.data"
    />
    <FormKit
        type="select"
        name="genre_id"
        label="Genre"
        :options="genres?.data"
    />
    <div class="grid grid-cols-2 gap-4">
        <FormKit
            type="number"
            name="duration"
            label="Duration (seconds)"
            :min="1"
        />
        <FormKit
            type="number"
            name="track_number"
            label="Track Number"
            :min="1"
        />
    </div>
    <div class="flex flex-col gap-1.5">
        <label class="text-sm font-medium">Audio File</label>
        <input
            type="file"
            accept=".mp3,.flac,.wav,.aac,.m4a,.ogg"
            @change="handleFileChange"
            class="block w-full text-sm text-gray-500 dark:text-gray-400 cursor-pointer file:mr-3 file:py-2 file:px-3 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-gray-100 dark:file:bg-gray-800 dark:file:text-gray-200 hover:file:bg-gray-200 dark:hover:file:bg-gray-700 file:cursor-pointer"
        />
        <p v-if="currentPath && !file" class="text-xs text-gray-500 dark:text-gray-400">
            Current: {{ currentPath.split('/').pop() }}
        </p>
    </div>
</template>
