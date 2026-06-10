<script setup lang="ts">
import TrackTable from '@/components/tracks/Table.vue';
import { TrackCollection } from '@/components/tracks/track';
import Header from '@coleus/support/components/card/Header.vue';
import { Link } from '@inertiajs/vue3';

defineProps<{
    collection?: TrackCollection;
}>();
</script>

<template>
    <UiCard>
        <template #header>
            <Header title="Tracks">
                <Link :href="route('music.tracks.create')">
                    <UiButton icon="lucide-plus">New</UiButton>
                </Link>
            </Header>
        </template>
        <TrackTable :collection="collection" />
    </UiCard>
</template>
