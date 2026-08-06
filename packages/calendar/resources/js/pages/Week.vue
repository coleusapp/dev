<script setup lang="ts">
import CalendarLayout from '@/layouts/CalendarLayout.vue';
import ViewSwitcher from '@/components/ViewSwitcher.vue';
import { buildWeekDates, isSameDay, months, weekdays } from '@/lib/dates';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const today = new Date();
const dates = computed(() => buildWeekDates(today));
</script>

<template>
    <Head title="Week" />

    <CalendarLayout>
        <div class="flex h-full min-h-0 flex-col gap-4">
            <div class="flex shrink-0 items-center justify-between">
                <h1 class="text-xl font-semibold">{{ months[today.getMonth()] }} {{ today.getFullYear() }}</h1>
                <ViewSwitcher active="week" />
            </div>

            <div
                class="grid min-h-0 flex-1 grid-cols-7 grid-rows-[auto_1fr] gap-px overflow-hidden rounded-md border border-gray-200 dark:border-gray-700"
            >
                <div
                    v-for="date in dates"
                    :key="date.toISOString()"
                    class="bg-gray-50 p-2 text-center dark:bg-gray-800"
                    :class="{ 'font-semibold text-blue-600 dark:text-blue-400': isSameDay(date, today) }"
                >
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ weekdays[date.getDay()] }}</p>
                    <p class="text-sm">{{ date.getDate() }}</p>
                </div>

                <div v-for="date in dates" :key="`body-${date.toISOString()}`" class="min-h-0 bg-white p-2 dark:bg-gray-900"></div>
            </div>
        </div>
    </CalendarLayout>
</template>
