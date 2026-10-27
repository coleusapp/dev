<script setup lang="ts">
import { noteStats } from '@/lib/stats';
import { refDebounced } from '@vueuse/core';
import { computed, toRef } from 'vue';

const props = defineProps<{
    body: string;
    createdAt: string;
    updatedAt: string;
}>();

/**
 * Recounted a moment after typing stops, not on every keystroke.
 */
const settledBody = refDebounced(toRef(props, 'body'), 300);
const stats = computed(() => noteStats(settledBody.value));

const number = new Intl.NumberFormat();
const dateTime = new Intl.DateTimeFormat(undefined, { dateStyle: 'medium', timeStyle: 'short' });

const rows = computed(() => [
    ['Words', number.format(stats.value.words)],
    ['Characters', number.format(stats.value.characters)],
    ['Characters (no spaces)', number.format(stats.value.charactersWithoutSpaces)],
    ['Paragraphs', number.format(stats.value.paragraphs)],
    ['Reading time', stats.value.readingMinutes ? `${stats.value.readingMinutes} min` : '—'],
    ['Created', dateTime.format(new Date(props.createdAt))],
    ['Modified', dateTime.format(new Date(props.updatedAt))],
]);
</script>

<template>
    <UiPopover :content="{ align: 'end', side: 'top' }">
        <UiButton color="neutral" variant="link" size="xs" class="text-dimmed" aria-label="Note info">
            {{ number.format(stats.words) }} {{ stats.words === 1 ? 'word' : 'words' }}
            <template v-if="stats.readingMinutes"> · {{ stats.readingMinutes }} min read</template>
        </UiButton>
        <template #content>
            <dl class="grid grid-cols-[auto_auto] gap-x-4 gap-y-1 p-3 text-sm" aria-label="Note info">
                <template v-for="[label, value] in rows" :key="label">
                    <dt class="text-dimmed">{{ label }}</dt>
                    <dd class="text-end tabular-nums">{{ value }}</dd>
                </template>
            </dl>
        </template>
    </UiPopover>
</template>
