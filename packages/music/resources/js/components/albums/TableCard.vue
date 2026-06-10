<script setup lang="ts">
import AlbumTable from '@/components/albums/Table.vue';
import { AlbumCollection } from '@/components/albums/album';
import Header from '@coleus/support/components/card/Header.vue';
import { Link } from '@inertiajs/vue3';

defineProps<{
    collection?: AlbumCollection;
}>();
</script>

<template>
    <UiCard>
        <template #header>
            <Header title="Albums">
                <Link :href="route('music.albums.create')">
                    <UiButton icon="lucide-plus">New</UiButton>
                </Link>
            </Header>
        </template>
        <AlbumTable :collection="collection" />
    </UiCard>
</template>
