<script setup lang="ts">
import EditArtist from '@/components/artists/Edit.vue';
import Header from '@coleus/support/components/card/Header.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <UiCard>
        <template #header>
            <Header title="Update Artist">
                <Link :href="route('music.artists.index')">
                    <UiButton trailing-icon="i-lucide-arrow-right" variant="ghost">All Artists</UiButton>
                </Link>
            </Header>
        </template>
        <EditArtist />
    </UiCard>
</template>
