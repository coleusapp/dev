<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps<{
    active: 'year' | 'month' | 'week' | 'day';
}>();

const views = [
    { label: 'Year', value: 'year', route: 'calendar.year' },
    { label: 'Month', value: 'month', route: 'calendar.month' },
    { label: 'Week', value: 'week', route: 'calendar.week' },
    { label: 'Day', value: 'day', route: 'calendar.day' },
] as const;
</script>

<template>
    <div class="flex gap-1 rounded-md border border-gray-300 p-1 dark:border-gray-700">
        <Link
            v-for="v in views"
            :key="v.value"
            :href="route(v.route, [], false)"
            class="rounded px-3 py-1 text-sm"
            :class="
                active === v.value
                    ? 'bg-gray-900 text-white dark:bg-white dark:text-gray-900'
                    : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800'
            "
        >
            {{ v.label }}
        </Link>
    </div>
</template>
