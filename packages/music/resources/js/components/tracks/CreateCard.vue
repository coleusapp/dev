<script setup lang="ts">
import CreateTrack from '@/components/tracks/Create.vue';
import Header from '@coleus/support/components/card/Header.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <UiCard>
        <template #header>
            <Header title="New Track">
                <Link :href="route('music.tracks.index')">
                    <UiButton trailing-icon="i-lucide-arrow-right" variant="ghost">All Tracks</UiButton>
                </Link>
            </Header>
        </template>
        <CreateTrack />
    </UiCard>
</template>
