<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { NavigationMenuItem } from '@nuxt/ui'
import { ref } from 'vue';

const items = ref<NavigationMenuItem[]>([
    {
        label: 'Music',
        icon: 'i-lucide-music',
        to: route('music.dashboard'),
        active: route().current('music.dashboard'),
        target: '_self',
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
        <div class="hidden md:flex w-full flex-col gap-2 h-full min-w-60 py-2 pl-2 pr-1">
            <UiNavigationMenu orientation="vertical" :items="items" class="w-full"  />
        </div>
    </aside>
</template>
