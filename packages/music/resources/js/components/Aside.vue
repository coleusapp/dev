<script setup lang="ts">
import type { NavigationMenuItem } from '@nuxt/ui'
import { ref } from 'vue';

const items = ref<NavigationMenuItem[]>([
    {
        label: 'Music',
        icon: 'i-lucide-music',
        to: route('music.dashboard', [], false),
        exact: true,
    },
    {
        label: 'Tracks',
        icon: 'i-lucide-music-2',
        to: route('music.tracks.index', [], false),
    },
    {
        label: 'Albums',
        icon: 'i-lucide-disc-3',
        to: route('music.albums.index', [], false),
    },
    {
        label: 'Artists',
        icon: 'i-lucide-mic-vocal',
        to: route('music.artists.index', [], false),
    },
    {
        label: 'Genres',
        icon: 'i-lucide-tag',
        to: route('music.genres.index', [], false),
    },
    {
        label: 'Playlists',
        icon: 'i-lucide-list-music',
        to: route('music.playlists.index', [], false),
    },
]);
</script>

<template>
    <aside>
        <div class="flex md:hidden w-full bg-gray-50 min-h-12 p-2 justify-end border-t border-gray-200">
            <UiDrawer :overlay="false">
                <UiButton color="neutral" variant="ghost" trailing-icon="i-lucide-menu" />
                <template #content>
                    <div class="p-2 overflow-y-scroll">
                        <UiNavigationMenu orientation="vertical" :items="items" class="w-full" />
                    </div>
                </template>
            </UiDrawer>
        </div>
        <div class="hidden md:flex w-full flex-col gap-2 h-full min-w-60">
            <UiNavigationMenu orientation="vertical" :items="items" class="w-full" />
        </div>
    </aside>
</template>
