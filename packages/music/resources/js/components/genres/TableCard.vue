<script setup lang="ts">
import GenreTable from '@/components/genres/Table.vue';
import { GenreCollection } from '@/components/genres/genre';
import Header from '@coleus/support/components/card/Header.vue';
import { Link } from '@inertiajs/vue3';

defineProps<{
    collection?: GenreCollection;
}>();
</script>

<template>
    <UiCard>
        <template #header>
            <Header title="Genres">
                <Link :href="route('music.genres.create')">
                    <UiButton icon="lucide-plus">New</UiButton>
                </Link>
            </Header>
        </template>
        <GenreTable :collection="collection" />
    </UiCard>
</template>
