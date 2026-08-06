<script setup lang="ts">
import ViewSwitcher from '@/components/ViewSwitcher.vue';
import CalendarLayout from '@/layouts/CalendarLayout.vue';
import { buildMonthGrid, isSameDay, isSameMonth, months, weekdays } from '@/lib/dates';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const today = new Date();
const grid = computed(() => buildMonthGrid(today.getFullYear(), today.getMonth()));
</script>

<template>
    <Head title="Month" />

    <CalendarLayout>
        <div class="flex h-full min-h-0 flex-col gap-4">
            <div class="flex shrink-0 items-center justify-between">
                <h1 class="text-xl font-semibold">{{ months[today.getMonth()] }} {{ today.getFullYear() }}</h1>
                <ViewSwitcher active="month" />
            </div>

            <div
                class="grid min-h-0 flex-1 grid-cols-7 grid-rows-[auto_repeat(6,minmax(0,1fr))] gap-1 overflow-hidden rounded-md border border-gray-200 p-2 dark:border-gray-700"
            >
                <div
                    v-for="day in weekdays"
                    :key="day"
                    class="bg-gray-50 p-2 text-center text-xs font-medium text-gray-500 dark:bg-gray-800 dark:text-gray-400"
                >
                    {{ day }}
                </div>

                <div
                    v-for="(cell, index) in grid"
                    :key="index"
                    class="min-h-0 overflow-hidden rounded border border-gray-200 bg-white p-1 text-sm dark:bg-gray-900 text-center"
                >
                    <span
                        class="p-2"
                        :class="[
                            { 'bg-blue-400! font-semibold text-blue-600 dark:text-blue-400': isSameDay(cell, today) },
                        ]"
                        >{{ cell.getDate() }}</span
                    >
                </div>
            </div>
        </div>
    </CalendarLayout>
</template>
