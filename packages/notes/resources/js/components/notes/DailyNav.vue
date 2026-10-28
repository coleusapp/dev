<script setup lang="ts">
import { isoDate } from '@/lib/dates';
import { computed } from 'vue';

/**
 * Stepping through daily notes (O9.2): the previous and next daily notes
 * (skipping days without one), the day itself, and a date picker that
 * opens (or makes) any day's note.
 */
const props = defineProps<{
    daily: { date: string; previous: number | null; next: number | null };
}>();

const emit = defineEmits<{
    openDay: [date: Date];
}>();

const day = computed(() => {
    const [year, month, date] = props.daily.date.split('-').map(Number);

    return new Date(year, month - 1, date);
});

const label = computed(() =>
    new Intl.DateTimeFormat(undefined, { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' }).format(day.value),
);

const isToday = computed(() => props.daily.date === isoDate(new Date()));

const pick = (event: Event): void => {
    const value = (event.target as HTMLInputElement).value;

    if (/^\d{4}-\d{2}-\d{2}$/.test(value) && value !== props.daily.date) {
        const [year, month, date] = value.split('-').map(Number);
        emit('openDay', new Date(year, month - 1, date));
    }
};
</script>

<template>
    <nav class="text-muted flex items-center gap-1 text-xs" aria-label="Daily notes">
        <UiButton
            v-if="daily.previous"
            icon="i-lucide-chevron-left"
            color="neutral"
            variant="ghost"
            size="xs"
            aria-label="Previous daily note"
            :to="route('notes.show', { note: daily.previous })"
        />
        <UiButton v-else icon="i-lucide-chevron-left" color="neutral" variant="ghost" size="xs" aria-label="No earlier daily note" disabled />
        <UiIcon name="i-lucide-calendar-days" class="size-3.5" />
        <span>{{ label }}</span>
        <UiButton
            v-if="daily.next"
            icon="i-lucide-chevron-right"
            color="neutral"
            variant="ghost"
            size="xs"
            aria-label="Next daily note"
            :to="route('notes.show', { note: daily.next })"
        />
        <UiButton v-else icon="i-lucide-chevron-right" color="neutral" variant="ghost" size="xs" aria-label="No later daily note" disabled />
        <label class="hover:bg-elevated relative ms-1 flex cursor-pointer items-center gap-1 rounded px-1.5 py-0.5">
            <span>Go to day…</span>
            <input
                type="date"
                :value="daily.date"
                class="absolute inset-0 cursor-pointer opacity-0"
                aria-label="Open the daily note for a day"
                @change="pick"
            />
        </label>
        <UiButton v-if="!isToday" size="xs" color="neutral" variant="link" @click="emit('openDay', new Date())">Today</UiButton>
    </nav>
</template>
