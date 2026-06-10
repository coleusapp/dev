<script setup lang="ts">
import { FormKit } from '@formkit/vue';
import { inject } from 'vue';
import { tracksKey } from '@/components/playlists/playlist';

const tracks = inject(tracksKey);
</script>

<template>
    <FormKit type="text" name="name" label="Name" validation="required" />
    <FormKit type="textarea" name="description" label="Description" />
    <FormKit type="repeater" name="tracks" label="Tracks" :min="0">
        <template #default="{ value }">
            <FormKit type="select" name="track_id" label="Track" :options="tracks?.data || []" />
        </template>
    </FormKit>
</template>
