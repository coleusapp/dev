<script setup lang="ts">
import DeleteAction from '@coleus/support/components/table/DeleteAction.vue';
import EditAction from '@coleus/support/components/table/EditAction.vue';

defineProps<{
    artistId: number | string;
}>();
</script>
<template>
    <div class="flex items-center justify-end">
        <EditAction :href="route('music.artists.edit', { artist: artistId })" />
        <DeleteAction :href="route('music.artists.destroy', { artist: artistId })" />
    </div>
</template>
