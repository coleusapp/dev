<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <AppLayout>
        <div class="flex w-full">
            <div class="min-w-60 py-2 pr-1 pl-2">
                <aside class="flex h-full">
                    <div class="flex w-full flex-col gap-2">
                        <Link
                            class="flex items-center justify-start gap-5 rounded-md px-2 py-2 text-sm font-bold hover:bg-white hover:shadow-sm dark:hover:bg-gray-800"
                            :class="{
                                'bg-white font-semibold shadow-sm hover:bg-gray-200 dark:bg-gray-800 dark:text-white dark:hover:bg-gray-600':
                                    route().current('health.dashboard'),
                            }"
                            :href="route('health.dashboard')"
                        >
                            <UiIcon name="i-lucide-heart-pulse" class="size-5" />
                            Health
                        </Link>
                        <Link
                            class="flex items-center justify-start gap-5 rounded-md px-2 py-2 text-sm font-bold hover:bg-white hover:shadow-sm dark:hover:bg-gray-800"
                        >
                            <UiIcon name="i-lucide-cog" class="size-5" />
                            Settings
                        </Link>
                    </div>
                </aside>
            </div>
            <div class="m-2 h-[calc(100vh-1rem)] w-full rounded-lg border border-gray-200 bg-white px-4 py-8 dark:border-gray-700 dark:bg-gray-950">
                <div class="mx-auto w-full max-w-7xl">
                    <slot />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
