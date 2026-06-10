<script setup lang="ts">
import DeleteAction from '@coleus/support/components/table/DeleteAction.vue';
import EditAction from '@coleus/support/components/table/EditAction.vue';

defineProps<{
    albumId: number | string;
}>();
</script>
<template>
    <div class="flex items-center justify-end">
        <EditAction :href="route('music.albums.edit', { album: albumId })" />
        <DeleteAction :href="route('music.albums.destroy', { album: albumId })" />
    </div>
</template>
