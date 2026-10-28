<script setup lang="ts">
import type { DateSuggestion } from '@/lib/dates';

defineProps<{
    choices: DateSuggestion[];
    selected: number;
    position: { left: number; top: number };
}>();

defineEmits<{
    choose: [choice: DateSuggestion];
}>();

const format = new Intl.DateTimeFormat(undefined, { weekday: 'short', month: 'short', day: 'numeric' });

const shown = (iso: string): string => {
    const [year, month, day] = iso.split('-').map(Number);

    return format.format(new Date(year, month - 1, day));
};
</script>

<template>
    <div
        v-if="choices.length"
        class="bg-default ring-default fixed z-50 max-h-72 w-60 overflow-y-auto rounded-md p-1 shadow-lg ring"
        :style="{ left: `${position.left}px`, top: `${position.top + 4}px` }"
        role="listbox"
        aria-label="Dates"
    >
        <button
            v-for="(choice, index) in choices"
            :key="choice.label"
            type="button"
            role="option"
            :aria-selected="index === selected"
            class="flex w-full items-center justify-between gap-2 rounded px-2 py-1.5 text-left text-sm"
            :class="index === selected ? 'bg-elevated' : 'hover:bg-elevated'"
            @mousedown.prevent="$emit('choose', choice)"
        >
            <span class="flex items-center gap-2"><UiIcon name="i-lucide-calendar" class="text-dimmed size-4" />{{ choice.label }}</span>
            <span class="text-dimmed text-xs">{{ shown(choice.iso) }}</span>
        </button>
    </div>
</template>
