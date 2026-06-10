<script setup lang="ts">
import DeleteAction from '@coleus/support/components/table/DeleteAction.vue';
import EditAction from '@coleus/support/components/table/EditAction.vue';

defineProps<{
    trackId: number | string;
}>();
</script>
<template>
    <div class="flex items-center justify-end">
        <EditAction :href="route('music.tracks.edit', { track: trackId })" />
        <DeleteAction :href="route('music.tracks.destroy', { track: trackId })" />
    </div>
</template>
