<script setup lang="ts">
import CalendarLayout from '@/layouts/CalendarLayout.vue';
import ViewSwitcher from '@/components/ViewSwitcher.vue';
import { hours, months, weekdays } from '@/lib/dates';
import { Head } from '@inertiajs/vue3';

const today = new Date();
</script>

<template>
    <Head title="Day" />

    <CalendarLayout>
        <div class="flex h-full min-h-0 flex-col gap-4">
            <div class="flex shrink-0 items-center justify-between">
                <h1 class="text-xl font-semibold">{{ weekdays[today.getDay()] }}, {{ months[today.getMonth()] }} {{ today.getDate() }}</h1>
                <ViewSwitcher active="day" />
            </div>

            <div class="grid min-h-0 flex-1 grid-rows-[repeat(24,minmax(0,1fr))] divide-y divide-gray-100 overflow-hidden rounded-md border border-gray-200 dark:divide-gray-800 dark:border-gray-700">
                <div v-for="hour in hours" :key="hour" class="flex items-center gap-2 overflow-hidden px-2 text-sm text-gray-500 dark:text-gray-400">
                    <span class="w-12 shrink-0">{{ hour.toString().padStart(2, '0') }}:00</span>
                </div>
            </div>
        </div>
    </CalendarLayout>
</template>
