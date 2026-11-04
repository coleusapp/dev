<script setup lang="ts">
import { TOOLBAR_GROUPS } from '@/editor/toolbar';
import type { Editor } from '@tiptap/vue-3';

/**
 * Formatting buttons for the note editor (editor/toolbar.ts). Buttons keep
 * the editor's focus and selection: mousedown is prevented.
 */
defineProps<{
    editor: Editor;
}>();
</script>

<template>
    <div class="note-toolbar flex flex-wrap items-center gap-0.5" role="toolbar" aria-label="Formatting">
        <template v-for="(group, index) in TOOLBAR_GROUPS" :key="index">
            <UiSeparator v-if="index > 0" orientation="vertical" class="mx-1 h-5" />
            <UiTooltip v-for="action in group" :key="action.id" :text="action.label">
                <UiButton
                    :icon="action.icon"
                    :aria-label="action.label"
                    :aria-pressed="action.isActive(editor)"
                    color="neutral"
                    :variant="action.isActive(editor) ? 'soft' : 'ghost'"
                    size="xs"
                    @mousedown.prevent
                    @click="action.run(editor)"
                />
            </UiTooltip>
        </template>
    </div>
</template>
