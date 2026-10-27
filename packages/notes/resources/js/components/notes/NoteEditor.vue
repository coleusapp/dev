<script setup lang="ts">
import type { NoteDetailData } from '@/components/notes/note';
import SaveStatus from '@/components/notes/SaveStatus.vue';
import { useAutosave } from '@/composables/useAutosave';
import { useLocalStorage, useMediaQuery } from '@vueuse/core';
import { computed, ref } from 'vue';

type EditorMode = 'edit' | 'split' | 'preview';

const props = defineProps<{
    note: NoteDetailData;
}>();

const emit = defineEmits<{
    saved: [note: NoteDetailData];
}>();

const body = ref(props.note.body);
const html = ref(props.note.html);

const { status, error, flush } = useAutosave(props.note, body, (note) => {
    html.value = note.html;
    emit('saved', note);
});

const preferredMode = useLocalStorage<EditorMode>('notes.editor-mode', 'edit');
const isWide = useMediaQuery('(min-width: 1280px)');

/**
 * Split view needs the room; on narrow screens it falls back to the editor.
 */
const mode = computed<EditorMode>(() => (preferredMode.value === 'split' && !isWide.value ? 'edit' : preferredMode.value));

const modeItems = computed(() => [
    { value: 'edit', icon: 'i-lucide-pencil', label: 'Edit' },
    ...(isWide.value ? [{ value: 'split', icon: 'i-lucide-columns-2', label: 'Split' }] : []),
    { value: 'preview', icon: 'i-lucide-eye', label: 'Preview' },
]);

const setMode = async (next: EditorMode): Promise<void> => {
    if (next !== 'edit') {
        await flush();
    }
    preferredMode.value = next;
};

const saveNow = (event: KeyboardEvent): void => {
    event.preventDefault();
    flush();
};
</script>

<template>
    <div class="flex h-full flex-col gap-2">
        <div class="flex min-h-0 flex-1 gap-4">
            <textarea
                v-if="mode !== 'preview'"
                v-model="body"
                class="h-full min-w-0 flex-1 resize-none bg-transparent font-mono text-sm leading-relaxed focus:outline-none"
                placeholder="Start writing..."
                aria-label="Note"
                autofocus
                @keydown.meta.s="saveNow"
                @keydown.ctrl.s="saveNow"
            />
            <!-- The HTML is rendered on the server with raw HTML escaped and unsafe links removed (NoteMarkdown). -->
            <!-- eslint-disable-next-line vue/no-v-html -->
            <article
                v-if="mode !== 'edit'"
                class="note-preview h-full min-w-0 flex-1 overflow-y-auto"
                :class="{ 'border-default border-l pl-4': mode === 'split' }"
                aria-label="Preview"
                v-html="html"
            />
        </div>
        <div class="flex items-center justify-between gap-2">
            <div class="flex items-center gap-1" role="group" aria-label="Editor mode">
                <UiButton
                    v-for="item in modeItems"
                    :key="item.value"
                    :icon="item.icon"
                    :aria-label="item.label"
                    :aria-pressed="mode === item.value"
                    :color="mode === item.value ? 'primary' : 'neutral'"
                    variant="ghost"
                    size="xs"
                    @click="setMode(item.value as EditorMode)"
                />
            </div>
            <SaveStatus :status="status" :error="error" @retry="flush" />
        </div>
    </div>
</template>
