<script setup lang="ts">
import { cellDisplay } from '@/lib/bases';
import { computed } from 'vue';

const props = defineProps<{
    value: unknown;
    error?: string;
}>();

/**
 * One value of a base (O11.1): text, a tick, chips for a list, or the
 * formula's error with its message on hover.
 */
const display = computed(() => cellDisplay(props.value));
</script>

<template>
    <UiTooltip v-if="error" :text="error">
        <span class="text-error inline-flex items-center gap-1 text-xs">
            <UiIcon name="i-lucide-triangle-alert" class="size-3.5" />
            <span class="sr-only">{{ error }}</span>
            Error
        </span>
    </UiTooltip>
    <UiIcon
        v-else-if="display.kind === 'check'"
        :name="display.checked ? 'i-lucide-square-check' : 'i-lucide-square'"
        class="text-muted size-4"
        :aria-label="display.checked ? 'Yes' : 'No'"
    />
    <span v-else-if="display.kind === 'list'" class="inline-flex flex-wrap gap-1">
        <UiBadge v-for="item in display.items" :key="item" color="neutral" variant="subtle" size="sm">{{ item }}</UiBadge>
    </span>
    <span v-else-if="display.kind === 'text'" :class="{ 'tabular-nums': display.numeric }">{{ display.text }}</span>
</template>
