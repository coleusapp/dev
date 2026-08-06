<script setup lang="ts">
import CalendarLayout from '@/layouts/CalendarLayout.vue';
import ViewSwitcher from '@/components/ViewSwitcher.vue';
import { months } from '@/lib/dates';
import { Head } from '@inertiajs/vue3';

const today = new Date();
</script>

<template>
    <Head title="Year" />

    <CalendarLayout>
        <div class="flex h-full min-h-0 flex-col gap-4">
            <div class="flex shrink-0 items-center justify-between">
                <h1 class="text-xl font-semibold">{{ today.getFullYear() }}</h1>
                <ViewSwitcher active="year" />
            </div>

            <div class="grid min-h-0 flex-1 grid-cols-3 grid-rows-4 gap-2 sm:grid-cols-4 sm:grid-rows-3">
                <div
                    v-for="(month, index) in months"
                    :key="month"
                    class="flex flex-col items-center justify-center rounded-md border border-gray-200 dark:border-gray-700"
                    :class="{ 'border-gray-600 dark:border-white': index === today.getMonth() }"
                >
                    <p class="text-sm font-medium">{{ month }}</p>
                </div>
            </div>
        </div>
    </CalendarLayout>
</template>
