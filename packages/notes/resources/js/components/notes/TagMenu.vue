<script setup lang="ts">
import HighlightedText from '@/components/notes/HighlightedText.vue';
import type { TagChoice } from '@/composables/useTagMenu';

defineProps<{
    choices: TagChoice[];
    selected: number;
    position: { left: number; top: number };
}>();

defineEmits<{
    choose: [choice: TagChoice];
}>();
</script>

<template>
    <div
        v-if="choices.length"
        class="bg-default ring-default fixed z-50 max-h-72 w-60 overflow-y-auto rounded-md p-1 shadow-lg ring"
        :style="{ left: `${position.left}px`, top: `${position.top + 4}px` }"
        role="listbox"
        aria-label="Tags"
    >
        <button
            v-for="(choice, index) in choices"
            :key="choice.tag"
            type="button"
            role="option"
            :aria-selected="index === selected"
            class="flex w-full items-center gap-2 rounded px-2 py-1.5 text-left text-sm"
            :class="index === selected ? 'bg-elevated' : 'hover:bg-elevated'"
            @mousedown.prevent="$emit('choose', choice)"
        >
            <UiIcon name="i-lucide-hash" class="text-dimmed size-4 shrink-0" />
            <span class="min-w-0 truncate"><HighlightedText :parts="choice.parts" /></span>
        </button>
    </div>
</template>
