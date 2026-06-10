<script setup lang="ts">
import EditPlaylist from '@/components/playlists/Edit.vue';
import Header from '@coleus/support/components/card/Header.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <UiCard>
        <template #header>
            <Header title="Update Playlist">
                <Link :href="route('music.playlists.index')">
                    <UiButton trailing-icon="i-lucide-arrow-right" variant="ghost">All Playlists</UiButton>
                </Link>
            </Header>
        </template>
        <EditPlaylist />
    </UiCard>
</template>
