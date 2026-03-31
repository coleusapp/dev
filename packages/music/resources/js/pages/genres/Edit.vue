<script setup lang="ts">
import EditCard from '@/components/genres/EditCard.vue';
import { GenreResource, resourceKey } from '@/components/genres/genre';
import MusicLayout from '@/layouts/MusicLayout.vue';
import { provide } from 'vue';

const props = defineProps<{
    resource: GenreResource;
}>();
provide(resourceKey, props.resource);
</script>

<template>
    <MusicLayout>
        <EditCard />
    </MusicLayout>
</template>
