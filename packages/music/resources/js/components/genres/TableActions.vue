<script setup lang="ts">
import DeleteAction from '@coleus/support/components/table/DeleteAction.vue';
import EditAction from '@coleus/support/components/table/EditAction.vue';

defineProps<{
    genreId: number | string;
}>();
</script>
<template>
    <div class="flex items-center justify-end">
        <EditAction :href="route('music.genres.edit', { genre: genreId })" />
        <DeleteAction :href="route('music.genres.destroy', { genre: genreId })" />
    </div>
</template>
