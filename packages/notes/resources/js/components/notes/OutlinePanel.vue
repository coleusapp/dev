<script setup lang="ts">
import type { OutlineHeading } from '@/lib/outline';
import { computed } from 'vue';

const props = defineProps<{
    headings: OutlineHeading[];
}>();

defineEmits<{
    jump: [heading: OutlineHeading];
}>();

/**
 * Indents relative to the note's top heading level, so a note that starts
 * at "##" isn't pushed in by a level it never uses.
 */
const topLevel = computed(() => Math.min(...props.headings.map((heading) => heading.level)));
</script>

<template>
    <nav aria-label="Outline" class="flex flex-col gap-0.5 text-sm">
        <p v-if="!headings.length" class="text-dimmed px-2 py-1 text-xs">No headings yet. Start a line with # to add one.</p>
        <button
            v-for="heading in headings"
            :key="`${heading.offset}`"
            type="button"
            class="hover:bg-elevated text-muted hover:text-default truncate rounded px-2 py-1 text-start"
            :class="{ 'text-default font-medium': heading.level === topLevel }"
            :style="{ paddingInlineStart: `${0.5 + (heading.level - topLevel) * 0.75}rem` }"
            :title="heading.text"
            @click="$emit('jump', heading)"
        >
            {{ heading.text }}
        </button>
    </nav>
</template>
