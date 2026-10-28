<script setup lang="ts">
import HighlightedText from '@/components/notes/HighlightedText.vue';
import type { LinkChoice } from '@/composables/useLinkMenu';

defineProps<{
    choices: LinkChoice[];
    selected: number;
    position: { left: number; top: number };
}>();

defineEmits<{
    choose: [choice: LinkChoice];
}>();

const icons: Record<LinkChoice['kind'], string> = { note: 'i-lucide-file-text', heading: 'i-lucide-heading', new: 'i-lucide-file-plus' };
</script>

<template>
    <div
        class="bg-default ring-default fixed z-50 max-h-72 w-72 overflow-y-auto rounded-md p-1 shadow-lg ring"
        :style="{ left: `${position.left}px`, top: `${position.top + 4}px` }"
        role="listbox"
        aria-label="Link to note"
    >
        <p v-if="!choices.length" class="text-dimmed px-2 py-1.5 text-sm">No matching notes</p>
        <button
            v-for="(choice, index) in choices"
            :key="choice.key"
            type="button"
            role="option"
            :aria-selected="index === selected"
            class="flex w-full items-center gap-2 rounded px-2 py-1.5 text-left text-sm"
            :class="index === selected ? 'bg-elevated' : 'hover:bg-elevated'"
            @mousedown.prevent="$emit('choose', choice)"
        >
            <UiIcon :name="icons[choice.kind]" class="text-dimmed size-4 shrink-0" />
            <span class="min-w-0 flex-1 truncate">
                <template v-if="choice.kind === 'new'">Link to new note “{{ choice.link }}”</template>
                <HighlightedText v-else :parts="choice.parts" />
            </span>
            <span v-if="choice.hint && choice.kind !== 'new'" class="text-dimmed max-w-28 shrink-0 truncate text-xs">{{ choice.hint }}</span>
        </button>
    </div>
</template>
