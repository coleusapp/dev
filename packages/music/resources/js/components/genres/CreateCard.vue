<script setup lang="ts">
import CreateGenre from '@/components/genres/Create.vue';
import Header from '@coleus/support/components/card/Header.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <UiCard>
        <template #header>
            <Header title="New Genre">
                <Link :href="route('music.genres.index')">
                    <UiButton trailing-icon="i-lucide-arrow-right" variant="ghost">All Genres</UiButton>
                </Link>
            </Header>
        </template>
        <CreateGenre />
    </UiCard>
</template>
