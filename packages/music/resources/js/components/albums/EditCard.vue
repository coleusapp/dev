<script setup lang="ts">
import EditAlbum from '@/components/albums/Edit.vue';
import Header from '@coleus/support/components/card/Header.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <UiCard>
        <template #header>
            <Header title="Update Album">
                <Link :href="route('music.albums.index')">
                    <UiButton trailing-icon="i-lucide-arrow-right" variant="ghost">All Albums</UiButton>
                </Link>
            </Header>
        </template>
        <EditAlbum />
    </UiCard>
</template>
