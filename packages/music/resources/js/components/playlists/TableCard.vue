<script setup lang="ts">
import PlaylistTable from '@/components/playlists/Table.vue';
import { PlaylistCollection } from '@/components/playlists/playlist';
import Header from '@coleus/support/components/card/Header.vue';
import { Link } from '@inertiajs/vue3';

defineProps<{
    collection?: PlaylistCollection;
}>();
</script>

<template>
    <UiCard>
        <template #header>
            <Header title="Playlists">
                <Link :href="route('music.playlists.create')">
                    <UiButton icon="lucide-plus">New</UiButton>
                </Link>
            </Header>
        </template>
        <PlaylistTable :collection="collection" />
    </UiCard>
</template>
