<script setup lang="ts">
import AppLayout from '@coleus/support/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    frames: { id: number; name: string; is_active: boolean; artworks_count: number }[];
}>();
</script>

<template>
    <Head title="Frames" />

    <AppLayout title="Frames">
        <div class="mx-auto flex w-full max-w-3xl flex-col gap-4">
            <div class="flex items-center justify-between gap-2">
                <p class="text-dimmed text-sm">Art frames showing public-domain paintings from the Art Institute of Chicago.</p>
                <UiButton icon="i-lucide-plus" :to="route('frame.create', {}, false)">New frame</UiButton>
            </div>

            <ul v-if="frames.length" class="divide-default border-default divide-y rounded-md border">
                <li v-for="frame in frames" :key="frame.id">
                    <Link :href="route('frame.edit', { frame: frame.id })" class="hover:bg-elevated/50 flex items-center gap-3 px-3 py-2.5">
                        <UiIcon name="i-lucide-frame" class="text-dimmed size-5 shrink-0" />
                        <span class="min-w-0 flex-1 truncate font-medium">{{ frame.name }}</span>
                        <span class="text-dimmed text-sm">{{ frame.artworks_count }} artworks</span>
                        <UiBadge :color="frame.is_active ? 'success' : 'neutral'" variant="subtle">
                            {{ frame.is_active ? 'Active' : 'Inactive' }}
                        </UiBadge>
                    </Link>
                </li>
            </ul>

            <UiEmpty v-else icon="i-lucide-frame" title="No frames yet" description="Create a frame to get a kiosk URL for it." />
        </div>
    </AppLayout>
</template>
