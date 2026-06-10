<script setup lang="ts">
import EditTrack from '@/components/tracks/Edit.vue';
import Header from '@coleus/support/components/card/Header.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <UiCard>
        <template #header>
            <Header title="Update Track">
                <Link :href="route('music.tracks.index')">
                    <UiButton trailing-icon="i-lucide-arrow-right" variant="ghost">All Tracks</UiButton>
                </Link>
            </Header>
        </template>
        <EditTrack />
    </UiCard>
</template>
