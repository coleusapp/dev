<script setup lang="ts">
import ArtistTable from '@/components/artists/Table.vue';
import { ArtistCollection } from '@/components/artists/artist';
import Header from '@coleus/support/components/card/Header.vue';
import { Link } from '@inertiajs/vue3';

defineProps<{
    collection?: ArtistCollection;
}>();
</script>

<template>
    <UiCard>
        <template #header>
            <Header title="Artists">
                <Link :href="route('music.artists.create')">
                    <UiButton icon="lucide-plus">New</UiButton>
                </Link>
            </Header>
        </template>
        <ArtistTable :collection="collection" />
    </UiCard>
</template>
