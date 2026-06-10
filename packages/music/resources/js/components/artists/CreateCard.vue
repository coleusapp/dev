<script setup lang="ts">
import CreateArtist from '@/components/artists/Create.vue';
import Header from '@coleus/support/components/card/Header.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <UiCard>
        <template #header>
            <Header title="New Artist">
                <Link :href="route('music.artists.index')">
                    <UiButton trailing-icon="i-lucide-arrow-right" variant="ghost">All Artists</UiButton>
                </Link>
            </Header>
        </template>
        <CreateArtist />
    </UiCard>
</template>
