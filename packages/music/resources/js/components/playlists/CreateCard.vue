<script setup lang="ts">
import CreatePlaylist from '@/components/playlists/Create.vue';
import Header from '@coleus/support/components/card/Header.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <UiCard>
        <template #header>
            <Header title="New Playlist">
                <Link :href="route('music.playlists.index')">
                    <UiButton trailing-icon="i-lucide-arrow-right" variant="ghost">All Playlists</UiButton>
                </Link>
            </Header>
        </template>
        <CreatePlaylist />
    </UiCard>
</template>
