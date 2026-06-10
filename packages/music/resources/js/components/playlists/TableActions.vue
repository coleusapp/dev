<script setup lang="ts">
import DeleteAction from '@coleus/support/components/table/DeleteAction.vue';
import EditAction from '@coleus/support/components/table/EditAction.vue';

defineProps<{
    playlistId: number | string;
}>();
</script>

<template>
    <div class="flex items-center justify-end">
        <EditAction :href="route('music.playlists.edit', { playlist: playlistId })" />
        <DeleteAction :href="route('music.playlists.destroy', { playlist: playlistId })" />
    </div>
</template>
