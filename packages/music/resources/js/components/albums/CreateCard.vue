<script setup lang="ts">
import CreateAlbum from '@/components/albums/Create.vue';
import Header from '@coleus/support/components/card/Header.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <UiCard>
        <template #header>
            <Header title="New Album">
                <Link :href="route('music.albums.index')">
                    <UiButton trailing-icon="i-lucide-arrow-right" variant="ghost">All Albums</UiButton>
                </Link>
            </Header>
        </template>
        <CreateAlbum />
    </UiCard>
</template>
